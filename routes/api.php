<?php

use App\Controllers\Api\{
    AuthController,
    PostController,
    UserController
};

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(PostController::class);
$app->router->registerRoutesFromController(UserController::class);
