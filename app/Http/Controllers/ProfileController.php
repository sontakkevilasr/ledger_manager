<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // ── Show profile page ─────────────────────────────────────
    public function show()
    {
        $user = Auth::user()->load('roles');

        // Last 10 login history for this user
        $loginHistory = LoginLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Recent activity by this user
        $recentActivity = \App\Models\ActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('profile', compact('user', 'loginHistory', 'recentActivity'));
    }

    // ── Update profile info ───────────────────────────────────
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'email'    => 'required|email|max:150|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        ActivityLogger::log(
            'updated', 'profile',
            $user->id, $user->name,
            'Updated own profile'
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── Change password ───────────────────────────────────────
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Current password is required.',
            'password.min'              => 'New password must be at least 8 characters.',
            'password.confirmed'        => 'New password confirmation does not match.',
        ]);

        // Verify current password
        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->with('tab', 'password');  // keep password tab open
        }

        // Prevent same password
        if (Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['password' => 'New password must be different from current password.'])
                ->with('tab', 'password');
        }

        $user->update(['password' => Hash::make($request->password)]);

        ActivityLogger::log(
            'updated', 'profile',
            $user->id, $user->name,
            'Changed own password'
        );

        return back()->with('success', 'Password changed successfully.');
    }
}
