<?php

use App\Controllers\Web\{
    AuthController,
    CommentController,
    HomeController,
    PostController
};

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(HomeController::class);
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(PostController::class);
$app->router->registerRoutesFromController(CommentController::class);
