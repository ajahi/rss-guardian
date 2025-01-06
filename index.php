<?php
require 'vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controllers\GuardianController;

$app = AppFactory::create();

// Default route to handle missing section
$app->get('/', function ($request, $response) {
    return $response->withHeader('Location', '/world')->withStatus(302);
});

// Route to handle different sections
$app->get('/{section}', [GuardianController::class, 'fetchSection']);

$app->run();
