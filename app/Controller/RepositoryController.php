<?php

namespace App\Controller;

use App\Dto\BaseResponse;
use App\Dto\CommitHistoryResponse;
use App\Dto\CommitInfoShortDto;
use App\Dto\RepositoryDto;
use App\Dto\RepositoryFileDto;
use App\Dto\RepositoryListResponse;
use App\Dto\RepositoryFolderDto;
use App\Dto\RepositoryLockDto;
use App\Dto\UserDto;
use App\Git;
use App\Model\RepositoryDao;
use CzProject\GitPhp\Exception;
use CzProject\GitPhp\GitException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Slim\Psr7\Stream as Stream;

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
            $gitRepo = $git->openRepo($repo->path);

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
                } // находим текущую папку

                $commitData = $gitRepo->getLastCommitForFile($file);
                $commitDto = new CommitInfoShortDto($commitData['id'], $commitData['timestamp'],
                    $commitData['message'], $commitData['author']);

                $lock = null;
                if (array_key_exists($file, $locksMap)) {
                    $lock = new RepositoryLockDto(
                        $locksMap[$file]->username,
                        $locksMap[$file]->created_on->getTimestamp()
                    );
                }

                $currentFolder->files[$filename] = new RepositoryFileDto($filename, $lock, $commitDto);
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

    function lockFile(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $body = $request->getParsedBody();
        $repoId = $body['repo_id'];
        $filename = $body['filename'];

        if ($repoId === null || $filename === null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        if ($dao->isFileLocked($repoId, $filename)) {
            return $response->withJson(new BaseResponse("Файл уже захвачен"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $git = new Git();
        $gitRepo = $git->openRepo($repo->path);
        $fileList = $gitRepo->listFiles();
        if (!in_array($filename, $fileList)) {
            return $response->withJson(new BaseResponse("Файл не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        $dao->createLock($repo->id, $filename, $user->id);

        return $response->withJson(new BaseResponse(null), 200, JSON_UNESCAPED_UNICODE);
    }

    function unlockFile(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $body = $request->getParsedBody();
        $repoId = $body['repo_id'];
        $filename = $body['filename'];

        if ($repoId === null || $filename === null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $lock = $dao->getLockByFilename($repo->id, $filename);
        if ($lock === null) {
            return $response->withJson(new BaseResponse("Файл не захвачен"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && $lock->user_id !== $user->id) {
            return $response->withJson(new BaseResponse("Файл захвачен другим пользователем"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $dao->removeLock($repo->id, $filename);

        return $response->withJson(new BaseResponse(null), 200, JSON_UNESCAPED_UNICODE);
    }

    function downloadFile(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $params = $request->getQueryParams();
        $repoId = $params['repo_id'] ?? null;
        $filename = $params['filename'] ?? null;
        $version = $params['version'] ?? 'HEAD';

        if ($repoId === null || $filename === null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $git = new Git();
        $gitRepo = $git->openRepo($repo->path);
//        $fileList = $gitRepo->listFiles();
//        if (!in_array($filename, $fileList)) {
//            return $response->withJson(new BaseResponse("Файл не найден"), 404,
//                JSON_UNESCAPED_UNICODE);
//        }

//        $path = REPOS_PATH . '/' . $repo->path . '/' . $filename;
//        $fh = fopen($path, 'rb');
//        $stream = new Stream($fh);

        try {
            $objId = $gitRepo->getObjectId($version, $filename);
        } catch (GitException $e) {
            return $response->withJson(new BaseResponse("Файл не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        $stream = new Stream($gitRepo->getBlobStream($objId));
        $path_split = explode('/', $filename);

        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . end($path_split) . '"')
            ->withBody($stream);
    }

    function getCommitHistoryForFile(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $params = $request->getQueryParams();
        $repoId = $params['repo_id'] ?? null;
        $filename = $params['filename'] ?? null;

        if ($repoId === null || $filename === null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $git = new Git();
        $gitRepo = $git->openRepo($repo->path);
        $fileList = $gitRepo->listFiles();
        if (!in_array($filename, $fileList)) {
            return $response->withJson(new BaseResponse("Файл не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        $commits = $gitRepo->getAllCommitsForFile($filename);
        $dtos = [];
        foreach ($commits as $commit) {
            $dtos[] = new CommitInfoShortDto($commit['id'], $commit['timestamp'], $commit['message'], $commit['author']);
        }

        return $response->withJson(new CommitHistoryResponse($dtos), 200, JSON_UNESCAPED_UNICODE);
    }

    function pushFile(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $body = $request->getParsedBody();
        $repoId = $body['repo_id'];
        $filename = html_entity_decode($body['filename']);
        $commitMessage = html_entity_decode($body['commit_message']);
        if (strlen($commitMessage) == 0) {
            $commitMessage = '(без сообщения)';
        }

        $newFile = $body['new'] == '1';

        if ($repoId === null || $filename === null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['file'];
        if ($uploadedFile == null || is_array($uploadedFile) || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $response->withJson(new BaseResponse("Отсутствует загруженный файл"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        if (!$newFile) {
            $lock = $dao->getLockByFilename($repo->id, $filename);
            if ($lock === null) {
                return $response->withJson(new BaseResponse("Файл не захвачен"), 400,
                    JSON_UNESCAPED_UNICODE);
            }

            if ($lock->user_id !== $user->id) {
                return $response->withJson(new BaseResponse("Файл захвачен другим пользователем"), 403,
                    JSON_UNESCAPED_UNICODE);
            }
        } else {
            if (is_file(REPOS_PATH . DIRECTORY_SEPARATOR . $repo->path . DIRECTORY_SEPARATOR . $filename)) {
                return $response->withJson(new BaseResponse("Файл существует"), 400, JSON_UNESCAPED_UNICODE);
            }
        }

        $git = new Git();
        $gitRepo = $git->openRepo($repo->path);
        $uploadedFile->moveTo($gitRepo->getRepositoryPath() . DIRECTORY_SEPARATOR . $filename);

        if (!$gitRepo->hasChanges()) {
            return $response->withJson(new BaseResponse("Файл идентичен предыдущей версии"), 400,
                JSON_UNESCAPED_UNICODE);
        }

        $gitRepo->addFile($filename);
        $gitRepo->commit($commitMessage);
        $gitRepo->addNote('Author: ' . $user->username);

        if (!$newFile)
            $dao->removeLock($repo->id, $filename);

        return $response->withJson(new BaseResponse(null), 200);
    }

    public function createRepository(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $params = $request->getQueryParams();
        $name = $params['name'] ?? null;
        $path = $params['folder_name'] ?? null;

        if ($user->role_id !== 1) {
            return $response->withJson(new BaseResponse("Нет прав на создание репозитория"), 403, JSON_UNESCAPED_UNICODE);
        }

        if ($name == null || $path == null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400, JSON_UNESCAPED_UNICODE);
        }

        if ($dao->getRepositoryByPath($path) != null) {
            return $response->withJson(new BaseResponse("Репозиторий уже существует"), 400, JSON_UNESCAPED_UNICODE);
        }

        $git = new Git();
        $gitRepo = $git->init($path);
        $gitRepo->config('core.quotepath', 'off');
        $gitRepo->config('user.email', GIT_EMAIL);
        $gitRepo->config('user.name', GIT_NAME);
        touch($gitRepo->getRepositoryPath() . DIRECTORY_SEPARATOR . '.gitkeep');
        $gitRepo->addFile('.gitkeep');
        $gitRepo->commit('Создан репозиторий');
        $gitRepo->addNote('Author: ' . $user->username);

        $dao->createRepository($name, $path);

        return $response->withJson(new BaseResponse(null), 200, JSON_UNESCAPED_UNICODE);
    }

    public function createFolder(Request $request, Response $response, $args) {
        $user = $request->getAttribute("user");
        $dao = new RepositoryDao();
        $params = $request->getQueryParams();
        $repoId = $params['repo_id'];
        $path = $params['path'] ?? null;

        if ($path == null || $repoId == null) {
            return $response->withJson(new BaseResponse("Отсутствует параметр"), 400, JSON_UNESCAPED_UNICODE);
        }

        $repo = $dao->getRepositoryById(intval($repoId));
        if ($repo === null) {
            return $response->withJson(new BaseResponse("Репозиторий не найден"), 404,
                JSON_UNESCAPED_UNICODE);
        }

        if ($user->role_id !== 1 && !$dao->userHasAccessToRepository($user->id, $repo->id)) {
            return $response->withJson(new BaseResponse("Нет доступа к репозиторию"), 403,
                JSON_UNESCAPED_UNICODE);
        }

        $fullPath = REPOS_PATH . '/' . $repo->path . '/' . $path;
        $filePath = $fullPath . '/.gitkeep';

        if (is_dir($fullPath)) {
            return $response->withJson(new BaseResponse("Папка существует"), 400, JSON_UNESCAPED_UNICODE);
        }

        if (!mkdir($fullPath, 0777, true) || !touch($filePath)) {
            return $response->withJson(new BaseResponse("Не удалось создать папку"), 500, JSON_UNESCAPED_UNICODE);
        }

        $git = new Git();
        $gitRepo = $git->openRepo($repo->path);

        $gitRepo->addFile($path . '/.gitkeep');
        $gitRepo->commit("Создана папка '$path'");
        $gitRepo->addNote('Author: ' . $user->username);

        return $response->withJson(new BaseResponse(null), 200);
    }
}
