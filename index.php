<?php

use Slim\Factory\AppFactory;
use App\Controllers\GuardianController;
use App\Services\Cache\FileCacheService;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$cacheService = new FileCacheService(__DIR__ . '/cache');
$guardianController = new GuardianController($cacheService);

// Default route for /api/section will use 'world'
$app->get('/', function ($request, $response) use ($guardianController) {
    return $guardianController->fetchSection($request, $response, ['section' => 'world']);
});

// Section route
$app->get('/{section}', [$guardianController, 'fetchSection']);
$app->run();