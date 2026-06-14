<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Define the project root so relative .env paths resolve correctly.
define('BASE_PATH', realpath(__DIR__ . '/../'));

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

use YasserElgammal\Green\Application;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\View\View;
use YasserElgammal\Green\Http\Middleware\CsrfMiddleware;
use YasserElgammal\Green\Security\Csrf\CsrfConfig;
use App\Exceptions\{ApiExceptionHandler, ErrorStatusResolver, ErrorViewResolver, Handler, WebExceptionHandler};
use App\Middleware\{LoggingMiddleware, LocaleMiddleware, TrimStringsMiddleware, ValidateSessionUserMiddleware, ValidationExceptionMiddleware};

$app = new Application();

$statusResolver = new ErrorStatusResolver();
$viewResolver = new ErrorViewResolver();
$handler = new Handler(
    new ApiExceptionHandler($statusResolver),
    new WebExceptionHandler($statusResolver, $viewResolver)
);

// Bind the skeleton's custom ExceptionHandler to the framework's container
$app->instance(\YasserElgammal\Green\Exceptions\ExceptionHandler::class, $handler);

// Add global middleware
$app->router->addGlobalMiddleware(ValidationExceptionMiddleware::class);
$app->router->addGlobalMiddleware(TrimStringsMiddleware::class);
$app->router->addGlobalMiddleware(LoggingMiddleware::class);
$app->router->addGlobalMiddleware(LocaleMiddleware::class);
$app->router->addGlobalMiddleware(ValidateSessionUserMiddleware::class);

// CSRF protection — load config and register middleware
$csrfConfig = new CsrfConfig(
    file_exists(__DIR__ . '/../config/csrf.php')
        ? require __DIR__ . '/../config/csrf.php'
        : []
);
$app->router->addGlobalMiddleware(new CsrfMiddleware($csrfConfig));

// Load routes
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

$request = Request::capture();

$response = $app->handle($request);
$response = $handler->handleResponse($response, $request);
$response->send();
