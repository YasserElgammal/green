<?php

use App\Controllers\WebController;
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\CommentController;

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(WebController::class);
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(PostController::class);
$app->router->registerRoutesFromController(CommentController::class);
