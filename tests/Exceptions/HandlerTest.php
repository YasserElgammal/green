<?php

namespace Tests\Exceptions;

use App\Exceptions\Contracts\ErrorResponderInterface;
use App\Exceptions\Handler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use YasserElgammal\Green\Exceptions\TokenMismatchException;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\View\View;

class HandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['APP_DEBUG'] = 'false';
        View::init(dirname(__DIR__, 2) . '/views');
    }

    public function testItDelegatesApiPathExceptionsToApiHandler(): void
    {
        $request = new Request(server: ['REQUEST_URI' => '/api/posts']);

        $response = (new Handler())->handle(new RuntimeException('Boom', 500), $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('"success":false', $response->getContent());
    }

    public function testItDelegatesJsonAcceptHeaderExceptionsToApiHandler(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/posts',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = (new Handler())->handle(new RuntimeException('Boom', 500), $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testItDelegatesBrowserRequestsToWebHandler(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/missing-page',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handle(new RuntimeException('Missing', 404), $request);

        $this->assertNotInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
    }

    public function testItRendersProductionWebErrorPagesWithoutExceptionDetails(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/posts',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handle(new RuntimeException('Sensitive database path', 500), $request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error', $response->getContent());
        $this->assertStringNotContainsString('Sensitive database path', $response->getContent());
        $this->assertStringNotContainsString('Stack Trace', $response->getContent());
    }

    public function testItKeepsDebugExceptionsOnOopsScreen(): void
    {
        $_ENV['APP_DEBUG'] = 'true';

        $request = new Request(server: [
            'REQUEST_URI' => '/missing-page',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handle(new RuntimeException('Debug detail', 404), $request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('DEVELOPER MODE ACTIVE', $response->getContent());
        $this->assertStringContainsString('Debug detail', $response->getContent());
        $this->assertStringContainsString('Stack Trace', $response->getContent());
    }

    public function testItUsesCsrfStatusCodeForWebRequests(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/posts',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handle(new TokenMismatchException(), $request);

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('Page Expired', $response->getContent());
    }

    public function testItConvertsRouterErrorResponsesForWebRequests(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/missing-page',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handleResponse(new Response('404 Not Found', 404), $request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Page Not Found', $response->getContent());
        $this->assertStringNotContainsString('404 Not Found', $response->getContent());
    }

    public function testItConvertsRouterErrorResponsesForApiRequests(): void
    {
        $request = new Request(server: [
            'REQUEST_URI' => '/api/missing-page',
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new Handler())->handleResponse(new Response('404 Not Found', 404), $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('"success":false', $response->getContent());
        $this->assertStringContainsString('Page Not Found', $response->getContent());
    }

    public function testItUsesInjectedResponders(): void
    {
        $apiResponder = new class implements ErrorResponderInterface {
            public function handle(\Throwable $e, Request $request): Response
            {
                return new Response('api-exception', 500);
            }

            public function handleStatus(int $statusCode): Response
            {
                return new Response('api-status', $statusCode);
            }
        };

        $webResponder = new class implements ErrorResponderInterface {
            public function handle(\Throwable $e, Request $request): Response
            {
                return new Response('web-exception', 500);
            }

            public function handleStatus(int $statusCode): Response
            {
                return new Response('web-status', $statusCode);
            }
        };

        $handler = new Handler($apiResponder, $webResponder);

        $apiRequest = new Request(server: ['REQUEST_URI' => '/api/posts']);
        $webRequest = new Request(server: ['REQUEST_URI' => '/posts']);

        $this->assertSame('api-exception', $handler->handle(new RuntimeException(), $apiRequest)->getContent());
        $this->assertSame('web-exception', $handler->handle(new RuntimeException(), $webRequest)->getContent());
        $this->assertSame('api-status', $handler->handleResponse(new Response('', 404), $apiRequest)->getContent());
        $this->assertSame('web-status', $handler->handleResponse(new Response('', 404), $webRequest)->getContent());
    }
}
