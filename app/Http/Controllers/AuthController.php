<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogger;

class AuthController extends Controller
{
    // ── Show login form ───────────────────────────────────────
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // ── Handle login ──────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required'    => 'Email is required.',
            'password.required' => 'Password is required.',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        // Check if user is active before attempting login
        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && ! $user->is_active) {
            ActivityLogger::loginFailed($request->email);
            return back()->withErrors(['email' => 'Your account has been deactivated. Contact admin.']);
        }

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Update last login timestamp
            Auth::user()->update(['last_login_at' => now()]);

            // Log successful login
            ActivityLogger::loginSuccess(Auth::id(), $request->email);

            return redirect()->intended(route('dashboard'));
        }

        // Log failed attempt
        ActivityLogger::loginFailed($request->email);

        return back()
            ->withErrors(['email' => 'Invalid email or password.'])
            ->withInput($request->only('email'));
    }

    // ── Handle logout ─────────────────────────────────────────
    public function logout(Request $request)
    {
        $user = Auth::user();

        ActivityLogger::logout($user->id, $user->email);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
