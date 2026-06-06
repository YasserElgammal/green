<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            session()->flash('error', 'Please log in to access the admin dashboard.');
            return redirect('/login');
        }

        if ((int) ($user->is_admin ?? 0) !== 1) {
            session()->flash('error', 'You do not have permission to access the admin dashboard.');
            return redirect('/');
        }

        $request->setAttribute('user', $user);

        return $next($request);
    }
}
