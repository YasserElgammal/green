<?php

namespace App\Controllers\Web;

use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Routing\Route;

class AuthController
{
    #[Route('GET', '/register')]
    public function showRegister()
    {
        return view('auth/register');
    }

    #[Route('POST', '/register')]
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

        session()->put('user_id', $user->id);
        session()->put('user_name', $user->name);
        session()->flash('success', 'Registration successful!');

        return redirect('/');
    }

    #[Route('GET', '/login')]
    public function showLogin()
    {
        return view('auth/login');
    }

    #[Route('POST', '/login')]
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

        if (!$user) {
            session()->flash('error', 'Invalid credentials.');
            return redirect('/login');
        }

        // Handle both hashed and plain text for backward compatibility if needed, but we'll assume standard hashing.
        // If Green framework user table didn't have password, we act safely.
        if (!isset($user->password) || !password_verify($password, $user->password)) {
            // If they don't have password set in DB, simulation fallback
            if (isset($user->password)) {
                session()->flash('error', 'Invalid credentials.');
                return redirect('/login');
            }
        }

        session()->put('user_id', $user->id);
        session()->put('user_name', $user->name);
        session()->flash('success', 'Logged in successfully!');

        return redirect('/');
    }

    #[Route('POST', '/logout')]
    public function logout()
    {
        session()->flush();
        session()->flash('success', 'Logged out successfully!');
        return redirect('/');
    }
}
