<?php

namespace Tests\Exceptions;

use App\Exceptions\Handler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
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
}
