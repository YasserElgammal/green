<?php

namespace App\Controllers\Web;

use App\Middleware\SessionAuthMiddleware;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\DeleteAccountPayload;
use App\Payloads\UpdateProfilePayload;
use App\Services\ProfileService;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class ProfileController
{
    public function __construct(private readonly ProfileService $profiles = new ProfileService())
    {
    }

    #[Route('GET', '/profile', [SessionAuthMiddleware::class], name: 'profile.show')]
    public function show(Request $request): string
    {
        return view('profile/show', ['user' => $request->getAttribute('user')]);
    }

    #[Route('POST', '/profile', [SessionAuthMiddleware::class], name: 'profile.update')]
    public function update(UpdateProfilePayload $payload): mixed
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();
        $users = new UserTable();
        $existing = $users->fetchFirst('email', $data['email']);

        if ($existing && (int) $existing->id !== (int) $user->id) {
            session()->flash('errors', ['email' => ['That email address is already in use.']]);
            return redirect(route('profile.show'));
        }

        $users->update($user->id, $data);
        session()->put('user_name', $data['name']);
        session()->flash('success', 'Profile updated.');

        return redirect(route('profile.show'));
    }

    #[Route('POST', '/profile/password', [SessionAuthMiddleware::class], name: 'profile.password.update')]
    public function changePassword(ChangePasswordPayload $payload): mixed
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        if (!$this->profiles->changePassword($user->id, $data['current_password'], $data['password'])) {
            session()->flash('errors', ['current_password' => ['The current password is incorrect.']]);
            return redirect(route('profile.show'));
        }

        session()->flash('success', 'Password changed successfully.');
        return redirect(route('profile.show'));
    }

    #[Route('POST', '/profile/delete', [SessionAuthMiddleware::class], name: 'profile.delete')]
    public function delete(DeleteAccountPayload $payload): mixed
    {
        $user = $payload->getAttribute('user');

        if (!$this->profiles->deleteAccount($user->id, $payload->validated()['password'])) {
            session()->flash('errors', ['delete_password' => ['The current password is incorrect.']]);
            return redirect(route('profile.show'));
        }

        auth()->logout();
        session()->flash('success', 'Your account has been deleted.');
        return redirect(route('home'));
    }
}
