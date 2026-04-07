@extends('layouts.app')
@section('title', 'Edit Agent — ' . $agent->name)
@section('page-title', 'Edit Agent')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-5">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('agents.index') }}" class="btn btn-link text-muted p-0">
        <i class="bi bi-arrow-left me-1"></i>Agents
    </a>
    <span class="text-muted">/</span>
    <span>{{ $agent->name }}</span>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pencil me-2"></i>Edit Agent</span>
        <span style="font-size:11px;color:#6c757d;">
            {{ $agent->transactions_count ?? $agent->transactions()->count() }} transactions linked
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('agents.update', $agent) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-500">Agent Name <span class="text-danger">*</span></label>
                <input type="text" name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $agent->name) }}"
                    style="text-transform:uppercase;"
                    required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-500">Phone Number</label>
                <input type="text" name="phone" class="form-control"
                    value="{{ old('phone', $agent->phone) }}"
                    placeholder="optional">
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                        name="is_active" id="is_active" value="1"
                        {{ $agent->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Agent
                </button>
                <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Cancel</a>

                <form method="POST" action="{{ route('agents.destroy', $agent) }}"
                    class="ms-auto"
                    onsubmit="return confirm('Delete {{ $agent->name }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
