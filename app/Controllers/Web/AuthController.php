<?php

namespace App\Controllers\Web;

use App\Middleware\GuestMiddleware;
use App\Middleware\SessionAuthMiddleware;
use App\Payloads\LoginPayload;
use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Routing\Route;

class AuthController
{
    #[Route('GET', '/register', [GuestMiddleware::class], name: 'register.form')]
    public function showRegister(): string
    {
        return view('auth/register');
    }

    #[Route('POST', '/register', [GuestMiddleware::class], name: 'register.store')]
    public function register(RegisterPayload $payload): mixed
    {
        $data = $payload->validated();
        $users = new UserTable();

        if ($users->fetchFirst('email', $data['email'])) {
            session()->flash('error', 'Email already in use.');
            return redirect(route('register.form'));
        }

        $user = $users->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        auth()->login($user);
        session()->flash('success', 'Registration successful!');

        return redirect(route('profile.show'));
    }

    #[Route('GET', '/login', [GuestMiddleware::class], name: 'login.form')]
    public function showLogin(): string
    {
        return view('auth/login');
    }

    #[Route('POST', '/login', [GuestMiddleware::class], name: 'login.store')]
    public function login(LoginPayload $payload): mixed
    {
        $credentials = $payload->validated();
        $user = (new UserTable())->fetchFirst('email', $credentials['email']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            session()->flash('error', 'The email or password is incorrect.');
            return redirect(route('login.form'));
        }

        auth()->login($user);
        session()->flash('success', 'Welcome back, ' . $user->name . '.');

        return redirect(route('profile.show'));
    }

    #[Route('POST', '/logout', [SessionAuthMiddleware::class], name: 'logout')]
    public function logout(): mixed
    {
        auth()->logout();
        session()->flash('success', 'You have been logged out.');

        return redirect(route('login.form'));
    }
}
