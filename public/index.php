<?php

use Slim\Factory\AppFactory;
use App\Controller\AuthController;
use App\Controller\RepositoryController;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$app = AppFactory::create();
$app->setBasePath('/api');

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->post('/login', AuthController::class);
$app->get('/repositories/list', RepositoryController::class . ':listRepos');

$app->run();
