<?php

use App\Controllers\Api\{
    AuthController,
    PostController,
    UserController,
    ProfileController,
};

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(PostController::class);
$app->router->registerRoutesFromController(UserController::class);
$app->router->registerRoutesFromController(ProfileController::class);
