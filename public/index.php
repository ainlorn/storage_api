<?php

use Slim\Factory\AppFactory;
use App\Controller\AuthController;
use App\Controller\RepositoryController;
use App\Middleware\AuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

setlocale(LC_CTYPE, "en_US.UTF-8");

$app = AppFactory::create();
$app->setBasePath(BASE_PATH);

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Session-Id')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->post('/login', AuthController::class);

// Authorized
$app->group('', function (\Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/repositories/list', RepositoryController::class . ':listRepos');
    $group->post('/repositories/lock', RepositoryController::class . ':lockFile');
    $group->post('/repositories/unlock', RepositoryController::class . ':unlockFile');
    $group->get('/repositories/downloadFile', RepositoryController::class . ':downloadFile');
    $group->post('/repositories/pushFile', RepositoryController::class . ':pushFile');
    $group->get('/repositories/commitHistory', RepositoryController::class . ':getCommitHistoryForFile');
    $group->get('/repositories/createRepository', RepositoryController::class . ':createRepository');
    $group->get('/repositories/createFolder', RepositoryController::class . ':createFolder');
})->add(new AuthMiddleware());

$app->run();
