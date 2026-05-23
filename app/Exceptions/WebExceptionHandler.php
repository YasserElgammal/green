<?php

namespace App\Exceptions;

use Throwable;
use YasserElgammal\Green\Exceptions\ExceptionHandler;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Http\ValidationException;
use YasserElgammal\Green\View\View;

class WebExceptionHandler extends ExceptionHandler
{
    protected function renderHtml(Throwable $e, int $statusCode, bool $isDebug): Response
    {
        if ($e instanceof ValidationException && $isDebug === false) {
            $errors = $e->getErrors();
            $firstError = reset($errors)[0] ?? 'The given data was invalid.';

            session()->flash('error', $firstError);
            session()->flash('errors', $errors);

            return redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }

        $viewParameters = [
            'title' => $this->getErrorTitle($statusCode),
            'message' => $isDebug ? $e->getMessage() : $this->cleanMessage($e->getMessage()),
            'debug' => $isDebug,
            'status_code' => $statusCode,
            'trace_id' => uniqid('ERR_'),
        ];

        if ($isDebug) {
            $viewParameters['exception'] = $e;
            $viewParameters['file'] = $e->getFile();
            $viewParameters['line'] = $e->getLine();
            $viewParameters['trace'] = $e->getTraceAsString();
        }

        $viewName = $this->errorViewName($statusCode);

        try {
            return new Response(View::render($viewName, $viewParameters), $statusCode);
        } catch (Throwable) {
            $content = "Oops! Something went wrong.\n\n";

            if ($isDebug) {
                $content .= "Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
            }

            return new Response($content, $statusCode, ['Content-Type' => 'text/plain']);
        }
    }

    private function errorViewName(int $statusCode): string
    {
        $specificViewPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . "/views/errors/{$statusCode}.twig";

        if (file_exists($specificViewPath)) {
            return "errors/{$statusCode}";
        }

        return 'errors/oops';
    }
}
