<?php

use App\Controllers\Web\AuthController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\LangController;
use App\Controllers\Web\ProfileController;

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(HomeController::class);
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(ProfileController::class);
$app->router->registerRoutesFromController(LangController::class);
