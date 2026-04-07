<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $this->checkSuperAdmin();

        $users = User::with('roles')->orderBy('name')->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->checkSuperAdmin();
        $roles = Role::orderBy('display_name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->checkSuperAdmin();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users',
            'email'    => 'required|email|max:150|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name'       => $data['name'],
            'username'   => strtolower($data['username']),
            'email'      => strtolower($data['email']),
            'password'   => Hash::make($data['password']),
            'is_active'  => true,
        ]);

        // Assign role
        \DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $data['role_id'],
        ]);

        return redirect()->route('users.index')
            ->with('success', "User [{$user->name}] created successfully.");
    }

    public function edit(User $user)
    {
        $this->checkSuperAdmin();
        $roles = Role::orderBy('display_name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkSuperAdmin();

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|max:150|unique:users,email,' . $user->id,
            'role_id'   => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name'      => $data['name'],
            'email'     => strtolower($data['email']),
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Update role
        \DB::table('user_roles')->where('user_id', $user->id)->delete();
        \DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $data['role_id'],
        ]);

        return redirect()->route('users.index')
            ->with('success', "User [{$user->name}] updated successfully.");
    }

    public function destroy(User $user)
    {
        $this->checkSuperAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User [{$name}] deleted.");
    }

    private function checkSuperAdmin(): void
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can manage users.');
        }
    }
}
