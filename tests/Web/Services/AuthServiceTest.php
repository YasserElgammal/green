<?php

namespace Tests\Web\Services;

use App\Tables\UserTable;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    public function testLoginStoresAdminStateInSession(): void
    {
        $this->seed();
        $admin = (new UserTable())->fetchByIdOrFail(2);
        session()->put('pre_auth', true);
        $previousId = session()->getId();

        auth()->login($admin);

        $this->assertNotSame($previousId, session()->getId());
        $this->assertSame(1, (int) session()->get('user_is_admin'));
    }

    public function testLoginStoresNonAdminStateInSession(): void
    {
        $this->seed();
        $user = (new UserTable())->fetchByIdOrFail(1);

        auth()->login($user);

        $this->assertSame(0, (int) session()->get('user_is_admin'));
    }

    public function testLogoutInvalidatesAuthenticatedSession(): void
    {
        $this->seed();
        $user = (new UserTable())->fetchByIdOrFail(1);
        auth()->login($user);
        $authenticatedId = session()->getId();

        auth()->logout();

        $this->assertNotSame($authenticatedId, session()->getId());
        $this->assertFalse(session()->has('user_id'));
        $this->assertFalse(session()->has('user_name'));
        $this->assertFalse(auth()->check());
    }
}
