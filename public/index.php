<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

use YasserElgammal\Green\Application;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\View\View;

// Initialize View engine
View::init(__DIR__ . '/../views');

$app = new Application();

// Add global middleware
$app->router->addGlobalMiddleware(\App\Middleware\LoggingMiddleware::class);

// Load routes
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

$request = Request::capture();

try {
    $response = $app->handle($request);
} catch (\Throwable $e) {
    $handler = new \YasserElgammal\Green\Exceptions\ExceptionHandler();
    $response = $handler->handle($e, $request);
}

$response->send();
