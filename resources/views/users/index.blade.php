{{-- ══════════════════════════════════════════════════════════════
     resources/views/users/index.blade.php
══════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')
@section('title','Users')
@section('page-title','Users & Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <span style="font-size:13px;color:#6c757d;">{{ $users->total() }} user(s)</span>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add User
    </a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-shield-lock me-2"></i>System Users</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th>Last Login</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#3b5bdb;">
                            {{ strtoupper(substr($u->name,0,2)) }}
                        </div>
                        <span class="fw-500">{{ $u->name }}</span>
                        @if($u->id === auth()->id())
                        <span class="badge" style="background:#eff3ff;color:#3b5bdb;font-size:10px;">You</span>
                        @endif
                    </div>
                </td>
                <td style="font-size:13px;">{{ $u->email }}</td>
                <td style="font-size:12px;color:#6c757d;">{{ $u->username }}</td>
                <td>
                    @foreach($u->roles as $role)
                    <span class="badge" style="background:#eff3ff;color:#3b5bdb;">{{ $role->display_name }}</span>
                    @endforeach
                </td>
                <td class="text-center">
                    <span class="badge {{ $u->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td style="font-size:12px;color:#6c757d;">
                    {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}
                </td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $u) }}"
                            onsubmit="return confirm('Delete user {{ $u->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">No users found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white">{{ $users->links() }}</div>
    @endif
</div>
@endsection
