<?php

namespace App\Controllers\Web;

use App\Middleware\SessionAuthMiddleware;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\UpdateProfilePayload;
use App\Payloads\DeleteAccountPayload;
use App\Services\ProfileService;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class ProfileController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    #[Route('GET', '/profile', [SessionAuthMiddleware::class])]
    public function index(Request $request): string
    {
        return view('profile/index', [
            'user' => $request->getAttribute('user')
        ]);
    }

    #[Route('POST', '/profile', [SessionAuthMiddleware::class])]
    public function update(UpdateProfilePayload $payload): mixed
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        // Unique email check
        $usersTable = new UserTable();
        $existing = $usersTable->fetchFirst('email', $data['email']);
        if ($existing && $existing->id !== $user->id) {
            session()->flash('error', t('profile.error_email_in_use') ?: 'Email already in use.');
            return redirect('/profile');
        }

        // Handle uploaded avatar if present
        $avatarFile = $_FILES['avatar'] ?? null;

        $avatarPath = $this->profileService->updateProfile($user->id, $data, $avatarFile);
        if ($avatarPath) {
            session()->put('user_avatar', $avatarPath);
        }

        // Update name in session if changed
        session()->put('user_name', $data['name']);
        
        session()->flash('success', t('profile.success_updated') ?: 'Profile updated successfully.');
        return redirect('/profile');
    }

    #[Route('POST', '/profile/password', [SessionAuthMiddleware::class])]
    public function changePassword(ChangePasswordPayload $payload): mixed
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $success = $this->profileService->changePassword(
            $user->id,
            $data['current_password'],
            $data['password']
        );

        if (!$success) {
            session()->flash('error', t('profile.error_current_password') ?: 'The provided current password does not match our records.');
            return redirect('/profile');
        }

        session()->flash('success', t('profile.success_password') ?: 'Password changed successfully.');
        return redirect('/profile');
    }

    #[Route('POST', '/profile/delete', [SessionAuthMiddleware::class])]
    public function deleteAccount(DeleteAccountPayload $payload): mixed
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $success = $this->profileService->deleteAccount($user->id, $data['password']);

        if (!$success) {
            session()->flash('error', t('profile.error_current_password') ?: 'The provided current password does not match our records.');
            return redirect('/profile');
        }

        // Invalidate the authenticated session and rotate its identifier.
        session()->invalidate();
        session()->flash('success', t('profile.success_deleted') ?: 'Your account has been deleted permanently.');

        return redirect('/');
    }
}
