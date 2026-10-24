<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use App\Tables\UserTable;
use YasserElgammal\Green\Auth\Authenticator;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Session\SessionManager;

final class SessionAuthenticator implements Authenticator
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly UserTable $users = new UserTable(),
    ) {
    }

    public function authenticate(Request $request): ?object
    {
        $userId = $this->session->get(SessionIdentityKey::forModel(User::class));

        if ($userId === null || $userId === '') {
            return null;
        }

        if ((!is_int($userId) && !is_string($userId))
            || (is_string($userId) && !ctype_digit($userId))
            || (int) $userId < 1) {
            $this->session->invalidate();
            return null;
        }

        $user = $this->users->fetchById($userId);

        if ($user === null) {
            $this->session->invalidate();
        }

        return $user;
    }
}
