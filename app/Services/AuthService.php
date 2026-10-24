<?php

namespace App\Services;

use App\Auth\SessionAuthenticator;
use App\Auth\SessionIdentityKey;
use App\Models\User;
use YasserElgammal\Green\Auth\Auth;

class AuthService
{
    public function __construct(
        private readonly ?Auth $auth = null,
    ) {
    }

    public function login(User $user): void
    {
        session()->regenerateId(true);
        session()->put(SessionIdentityKey::forModel(User::class), $user->id);
        session()->put('user_name', $user->name);
        ($this->auth ?? app(Auth::class))->synchronizeUser($user, SessionAuthenticator::class);
    }

    public function logout(): void
    {
        session()->invalidate();
        ($this->auth ?? app(Auth::class))->synchronizeUser(null, SessionAuthenticator::class);
    }
}
