<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\ProfileController;

/** @var \YasserElgammal\Green\Application $app */
$app->router->registerRoutesFromController(AuthController::class);
$app->router->registerRoutesFromController(ProfileController::class);
