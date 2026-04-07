{{-- ══════════════════════════════════════════════════════════════
     resources/views/users/create.blade.php
══════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')
@section('title','Add User')
@section('page-title','Add User')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-person-plus me-2"></i>New User</div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                        value="{{ old('username') }}" required>
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                        <option value="">— Select Role —</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                            @if($role->description) — {{ $role->description }} @endif
                        </option>
                        @endforeach
                    </select>
                    @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Min 8 characters" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create User</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
