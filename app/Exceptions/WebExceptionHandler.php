<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ErrorResponderInterface;
use Throwable;
use YasserElgammal\Green\Exceptions\ExceptionHandler;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Http\ValidationException;
use YasserElgammal\Green\View\View;

class WebExceptionHandler extends ExceptionHandler implements ErrorResponderInterface
{
    public function __construct(
        private ?ErrorStatusResolver $statusResolver = null,
        private ?ErrorViewResolver $viewResolver = null,
        private ?bool $debug = null,
    ) {
        parent::__construct();
        $this->statusResolver ??= new ErrorStatusResolver();
        $this->viewResolver ??= new ErrorViewResolver();
    }

    protected function isDebug(): bool
    {
        return $this->debug ?? parent::isDebug();
    }

    public function handle(Throwable $e, Request $request): Response
    {
        $traceId = uniqid('ERR_', true);

        return $this->renderHtml($e, $this->statusResolver->resolve($e), $this->isDebug(), $traceId);
    }

    public function handleStatus(int $statusCode): Response
    {
        return $this->renderErrorPage($statusCode);
    }

    protected function renderHtml(Throwable $e, int $statusCode, bool $isDebug, string $traceId): Response
    {
        if ($e instanceof ValidationException && $isDebug === false) {
            $errors = $e->getErrors();
            $firstError = reset($errors)[0] ?? 'The given data was invalid.';

            session()->flash('error', $firstError);
            session()->flash('errors', $errors);

            return redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }

        if ($isDebug) {
            return $this->renderDebugException($e, $statusCode, $traceId);
        }

        return $this->renderErrorPage($statusCode);
    }

    private function renderDebugException(Throwable $e, int $statusCode, string $traceId): Response
    {
        $viewParameters = [
            'title' => $this->getErrorTitle($statusCode),
            'message' => $e->getMessage(),
            'debug' => true,
            'status_code' => $statusCode,
            'trace_id' => $traceId,
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        try {
            return new Response(View::render('errors/oops', $viewParameters), $statusCode);
        } catch (Throwable) {
            $content = "Oops! Something went wrong.\n\n";
            $content .= "Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";

            return new Response($content, $statusCode, ['Content-Type' => 'text/plain']);
        }
    }

    private function renderErrorPage(int $statusCode): Response
    {
        $viewParameters = [
            'code' => $statusCode,
            'status_code' => $statusCode,
            'home_url' => '/',
            'show_home_button' => true,
            'show_back_button' => true,
        ];

        $viewName = $this->errorViewName($statusCode);

        try {
            return new Response(View::render($viewName, $viewParameters), $statusCode);
        } catch (Throwable) {
            return new Response(
                "{$statusCode} {$this->getErrorTitle($statusCode)}",
                $statusCode,
                ['Content-Type' => 'text/plain']
            );
        }
    }

    private function errorViewName(int $statusCode): string
    {
        if ($this->viewResolver->hasView($statusCode)) {
            return $this->viewResolver->view($statusCode);
        }

        return 'errors/layout';
    }
}
