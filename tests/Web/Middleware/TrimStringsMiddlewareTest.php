<?php

namespace Tests\Web\Middleware;

use App\Middleware\TrimStringsMiddleware;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class TrimStringsMiddlewareTest extends TestCase
{
    public function testItTrimsQueryAndPostStringsRecursively(): void
    {
        $request = new Request(
            ['search' => '  admin  '],
            [
                'name' => '  Green  ',
                'roles' => [' admin ', ' editor '],
                'active' => true,
            ],
            [],
            ['avatar' => ['name' => '  keep-file-name.png  ']],
        );

        $middleware = new TrimStringsMiddleware();

        $response = $middleware->handle($request, function (Request $request) {
            $this->assertSame('admin', $request->query['search']);
            $this->assertSame('Green', $request->post['name']);
            $this->assertSame(['admin', 'editor'], $request->post['roles']);
            $this->assertTrue($request->post['active']);
            $this->assertSame('  keep-file-name.png  ', $request->files['avatar']['name']);

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }
}
