<?php

use App\Controllers\Web\AuthController;
use App\Controllers\Web\CommentController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\LangController;
use App\Controllers\Web\PostController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\Admin\CommentController as AdminCommentController;
use App\Controllers\Web\Admin\DashboardController;
use App\Controllers\Web\Admin\PostController as AdminPostController;
use App\Controllers\Web\Admin\StatisticsController;
use App\Controllers\Web\Admin\UserController;

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(DashboardController::class);
$app->router->registerRoutesFromController(UserController::class);
$app->router->registerRoutesFromController(AdminPostController::class);
$app->router->registerRoutesFromController(AdminCommentController::class);
$app->router->registerRoutesFromController(StatisticsController::class);
$app->router->registerRoutesFromController(HomeController::class);
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(PostController::class);
$app->router->registerRoutesFromController(CommentController::class);
$app->router->registerRoutesFromController(LangController::class);
$app->router->registerRoutesFromController(ProfileController::class);
