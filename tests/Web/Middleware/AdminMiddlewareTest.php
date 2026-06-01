<?php

namespace Tests\Web\Middleware;

use App\Middleware\AdminMiddleware;
use App\Tables\UserTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class AdminMiddlewareTest extends TestCase
{
    private AdminMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->middleware = new AdminMiddleware();
    }

    protected function tearDown(): void
    {
        auth()->logout();

        parent::tearDown();
    }

    public function testGuestIsRedirectedAwayFromAdminRoutes(): void
    {
        $response = $this->middleware->handle(new Request(), fn () => new Response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['Please log in to access the admin dashboard.'], session()->getFlash('error'));
    }

    public function testNonAdminIsRedirectedAwayFromAdminRoutes(): void
    {
        $user = (new UserTable())->fetchByIdOrFail(1);
        auth()->login($user);

        $response = $this->middleware->handle(new Request(), fn () => new Response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['You do not have permission to access the admin dashboard.'], session()->getFlash('error'));
    }

    public function testAdminCanAccessAdminRoutes(): void
    {
        $admin = (new UserTable())->fetchByIdOrFail(2);
        auth()->login($admin);
        $request = new Request();

        $response = $this->middleware->handle($request, function (Request $request) use ($admin) {
            $this->assertSame($admin->id, $request->getAttribute('user')->id);

            return new Response('ok');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
