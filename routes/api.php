<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\ProfileController;

/** @var \YasserElgammal\Green\Application $app */
if (config('auth.jwt.enabled', false)) {
    $app->router->registerRoutesFromController(AuthController::class);
    $app->router->registerRoutesFromController(ProfileController::class);
}
