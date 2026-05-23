<?php

namespace App\Controllers\Web;

use App\Middleware\GuestMiddleware;
use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Routing\Route;

class AuthController
{
    #[Route('GET', '/register', [GuestMiddleware::class])]
    public function showRegister()
    {
        return view('auth/register');
    }

    #[Route('POST', '/register', [GuestMiddleware::class])]
    public function register(RegisterPayload $payload)
    {
        $data = $payload->validated();
        
        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];

        $users = new UserTable();
        
        // Check if email exists
        if ($users->fetchFirst('email', $email)) {
            session()->flash('error', 'Email already in use.');
            return redirect('/register');
        }

        $user = $users->insert([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        auth()->login($user);
        session()->flash('success', 'Registration successful!');

        return redirect('/');
    }

    #[Route('GET', '/login', [GuestMiddleware::class])]
    public function showLogin()
    {
        return view('auth/login');
    }

    #[Route('POST', '/login', [GuestMiddleware::class])]
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            session()->flash('error', 'Email and password are required.');
            return redirect('/login');
        }

        $users = new UserTable();
        $user = $users->fetchFirst('email', $email);

        if (!$user || !isset($user->password) || !password_verify($password, $user->password)) {
            session()->flash('error', 'Invalid credentials.');
            return redirect('/login');
        }

        auth()->login($user);
        session()->flash('success', 'Logged in successfully!');

        return redirect('/');
    }

    #[Route('POST', '/logout')]
    public function logout()
    {
        auth()->logout();
        session()->flash('success', 'Logged out successfully!');
        return redirect('/');
    }
}
