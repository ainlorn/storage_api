<?php

namespace App\Controller;

use App\Dto\RepositoryDto;
use App\Dto\RepositoryFileDto;
use App\Dto\RepositoryListResponse;
use App\Dto\RepositoryFolderDto;
use App\Dto\UserDto;
use App\Git;
use App\Model\RepositoryDao;
use CzProject\GitPhp\Exception;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;

class RepositoryController {
    function listRepos(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $git = new Git();

        if ($user->isAdmin())
            $repos = $dao->getAllRepositories();
        else
            $repos = $dao->getRepositoriesForUser($user->id);

        $dtos = array();

        foreach ($repos as $repo) {
            $gitRepo = $git->open($repo->path);

            $folders = $gitRepo->listFolders();
            $tree = new RepositoryFolderDto("/", array());
            foreach ($folders as $folder) {
                $splitPath = explode("/", $folder);
                $currentFolder = $tree;
                foreach ($splitPath as $subFolder) {
                    if (!array_key_exists($subFolder, $currentFolder->files)) {
                        $currentFolder->files[$subFolder] = new RepositoryFolderDto($subFolder, array());
                    }
                    $currentFolder = $currentFolder->files[$subFolder];
                }
            }

            $locks = $dao->getLocks($repo->id);
            $locksMap = array();
            foreach ($locks as $lock) {
                $locksMap[$lock->filename] = $lock;
            }

            $files = $gitRepo->listFiles();
            foreach ($files as $file) {
                $splitPath = explode("/", $file);
                $filename = $splitPath[count($splitPath) - 1];
                array_pop($splitPath);

                if (str_starts_with($filename, ".git"))
                    continue; // ignore git files

                $currentFolder = $tree;
                foreach ($splitPath as $subFolder) {
                    if (!array_key_exists($subFolder, $currentFolder->files)) {
                        throw new Exception("File tree parsing error");
                    }
                    $currentFolder = $currentFolder->files[$subFolder];
                }

                $locked = false;
                $user = null;
                if (array_key_exists($file, $locksMap)) {
                    $locked = true;
                    $user = $locksMap[$file]->username;
                }

                $currentFolder->files[$filename] = new RepositoryFileDto($filename, $locked, $user);
            }

            $dtos[] = new RepositoryDto($repo->id, $repo->name,
                RepositoryController::array_values_recursive($tree->files));
        }

        return $response->withJson(new RepositoryListResponse($dtos), 200, JSON_UNESCAPED_UNICODE);
    }

    static function array_values_recursive($arr) {
        $arr2 = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr2[] = RepositoryController::array_values_recursive($value);
            } elseif ($value instanceof RepositoryFolderDto) {
                $value->files = RepositoryController::array_values_recursive($value->files);
                $arr2[] = $value;
            } else {
                $arr2[] = $value;
            }
        }

        return $arr2;
    }
}