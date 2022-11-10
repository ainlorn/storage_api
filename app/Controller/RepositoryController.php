<?php

namespace App\Controller;

use App\Dto\RepositoryDto;
use App\Dto\RepositoryFileDto;
use App\Dto\RepositoryListResponse;
use App\Dto\RepositoryLockDto;
use App\Dto\UserDto;
use App\Git;
use App\Model\RepositoryDao;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;

class RepositoryController {
    function listRepos(Request $request, Response $response, $args) {
        $dao = new RepositoryDao();
        $git = new Git();
        $repos = $dao->getAllRepositories();
        $dtos = array();

        foreach ($repos as $repo) {
            $gitRepo = $git->open($repo->path);
            $files = $gitRepo->listFiles();
            $locks = $dao->getLocks($repo->id);
            $lockDtos = array();
            foreach ($locks as $lock) {
                $lockDtos[] = new RepositoryLockDto(
                    $lock->filename,
                    new UserDto($lock->user_id, $lock->username, $lock->real_name, $lock->role_id),
                    $lock->user_id === 1,
                    $lock->created_on->getTimestamp()
                );
            }

            $dtos[] = new RepositoryDto($repo->id, $repo->name, $files, $lockDtos);
        }

        return $response->withJson(new RepositoryListResponse($dtos), 200, JSON_UNESCAPED_UNICODE);
    }
}