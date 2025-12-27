<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class CodeLoginController
{
    public function show()
    {
        return view('auth.code-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $ip = $request->ip();
        $code = strtoupper($request->input('code'));

        // Basic rate limiting per IP
        $key = 'code-login:'.Str::lower($ip);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['code' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($key, 30);

        $user = User::where('login_code', $code)
            ->whereNotNull('login_code_expiration')
            ->first();

        if (!$user) {
            return back()->withErrors(['code' => 'Invalid code.']);
        }

        if (now()->isAfter($user->login_code_expiration)) {
            return back()->withErrors(['code' => 'Code expired.']);
        }

        if ($user->login_code_valid_ip !== $ip) {
            return back()->withErrors(['code' => 'Code not valid from this IP.']);
        }

        // Prevent logging in admins via code
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return back()->withErrors(['code' => 'This user must log in with password.']);
        }

        // Clear code after use (single-use semantics)
        $user->login_code = null;
        $user->login_code_expiration = null;
        $user->login_code_valid_ip = null;
        $user->save();

        Auth::login($user);

        return redirect()->intended('/');
    }
}
