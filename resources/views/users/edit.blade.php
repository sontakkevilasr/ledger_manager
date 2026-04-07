@extends('layouts.app')
@section('title','Edit User')
@section('page-title','Edit User')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit: {{ $user->name }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            {{ $user->roles->first()?->id == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                            {{ $user->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-12"><hr class="my-1"><small class="text-muted">Leave password blank to keep unchanged.</small></div>
                <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Min 8 characters">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update User</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
