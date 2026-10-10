<?php

namespace Tests\Services;

use App\Services\ProfileService;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use Tests\TestCase;

class ServiceAndTransformerTest extends TestCase
{
    public function testAuthServiceManagesSessionAuthentication(): void
    {
        $user = $this->createUser();
        auth()->login($user);
        $this->assertTrue(auth()->check());
        $this->assertFalse(auth()->guest());
        $this->assertSame($user->id, session()->get('user_id'));
        auth()->logout();
        $this->assertTrue(auth()->guest());
        $this->assertNull(session()->get('user_id'));
    }

    public function testAuthServiceIssuesAndResolvesJwt(): void
    {
        $user = $this->createUser();
        $resolved = auth()->resolveFromJwt(auth()->issueToken($user));
        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->id);
        $this->assertNull(auth()->resolveFromJwt('not-a-token'));
    }

    public function testProfileServiceChangesPasswordAndDeletesAccount(): void
    {
        $users = new UserTable();
        $service = new ProfileService();
        $user = $this->createUser();
        $this->assertFalse($service->changePassword($user->id, 'wrong', 'new-password'));
        $this->assertTrue($service->changePassword($user->id, 'password', 'new-password'));
        $this->assertTrue(password_verify('new-password', $users->fetchByIdOrFail($user->id)->password));
        $this->assertFalse($service->deleteAccount($user->id, 'wrong'));
        $this->assertTrue($service->deleteAccount($user->id, 'new-password'));
        $this->assertNull($users->fetchById($user->id));
    }

    public function testUserTransformerReturnsOnlyPublicUserFields(): void
    {
        $user = $this->createUser();
        $data = (new UserTransformer())->transform($user);
        $this->assertSame(['id' => (int) $user->id, 'name' => $user->name, 'email' => $user->email], $data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('refresh_token', $data);
    }
}
