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

        auth()->login($admin);

        $this->assertSame(1, (int) session()->get('user_is_admin'));
    }

    public function testLoginStoresNonAdminStateInSession(): void
    {
        $this->seed();
        $user = (new UserTable())->fetchByIdOrFail(1);

        auth()->login($user);

        $this->assertSame(0, (int) session()->get('user_is_admin'));
    }
}
