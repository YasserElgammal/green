<?php

namespace Tests\Jwt\Services;

use App\Services\ProfileService;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use App\Auth\JwtAuthenticator;
use YasserElgammal\Green\Http\Request;
use Tests\JwtTestCase;

class ServiceAndTransformerTest extends JwtTestCase
{
    public function testAuthServiceIssuesAndResolvesJwt(): void
    {
        $user = $this->createUser();
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer ' . app(\App\Services\JwtTokenService::class)->issueToken($user)]);
        $resolved = (app(JwtAuthenticator::class))->authenticate($request);
        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->id);
    }


}
