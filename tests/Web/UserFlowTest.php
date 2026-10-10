<?php

namespace Tests\Web;

use App\Controllers\Web\AuthController;
use App\Controllers\Web\ProfileController;
use App\Payloads\LoginPayload;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\DeleteAccountPayload;
use App\Payloads\UpdateProfilePayload;
use App\Tables\UserTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\RedirectResponse;
use YasserElgammal\Green\Http\Request;

class UserFlowTest extends TestCase
{
    public function testUserCanLogInUpdateProfileAndLogOut(): void
    {
        $this->createUser();
        $auth = new AuthController();

        $login = new LoginPayload(new Request([], [
            'email' => 'user@example.com',
            'password' => 'password',
        ]));
        $this->assertInstanceOf(RedirectResponse::class, $auth->login($login));
        $this->assertSame(1, (int) session()->get('user_id'));

        $request = new Request([], [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);
        $request->setAttribute('user', (new UserTable())->fetchByIdOrFail(1));

        $profile = new ProfileController();
        $this->assertInstanceOf(RedirectResponse::class, $profile->update(new UpdateProfilePayload($request)));
        $this->assertSame('Updated User', (new UserTable())->fetchByIdOrFail(1)->name);
        $this->assertSame('Updated User', session()->get('user_name'));

        $this->assertInstanceOf(RedirectResponse::class, $auth->logout());
        $this->assertFalse(session()->has('user_id'));
    }

    public function testProfileRejectsAnEmailUsedByAnotherUser(): void
    {
        $user = $this->createUser();
        $this->createUser('other@example.com');

        $request = new Request([], ['name' => 'Demo User', 'email' => 'other@example.com']);
        $request->setAttribute('user', $user);
        (new ProfileController())->update(new UpdateProfilePayload($request));

        $this->assertSame('user@example.com', (new UserTable())->fetchByIdOrFail($user->id)->email);
        $this->assertNotEmpty(session()->getFlash('errors'));
    }

    public function testUserCanChangePassword(): void
    {
        $user = $this->createUser();
        $request = new Request([], [
            'current_password' => 'password',
            'password' => 'new-password',
            'confirm_password' => 'new-password',
        ]);
        $request->setAttribute('user', $user);

        $response = (new ProfileController())->changePassword(new ChangePasswordPayload($request));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(password_verify('new-password', (new UserTable())->fetchByIdOrFail($user->id)->password));
    }

    public function testUserCanDeleteAccount(): void
    {
        $user = $this->createUser();
        auth()->login($user);
        $request = new Request([], ['password' => 'password']);
        $request->setAttribute('user', $user);

        $response = (new ProfileController())->delete(new DeleteAccountPayload($request));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNull((new UserTable())->fetchById($user->id));
        $this->assertFalse(session()->has('user_id'));
    }
}
