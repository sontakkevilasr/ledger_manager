@extends('layouts.app')
@section('title', 'Add Agent')
@section('page-title', 'Add Agent')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-5">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('agents.index') }}" class="btn btn-link text-muted p-0">
        <i class="bi bi-arrow-left me-1"></i>Agents
    </a>
    <span class="text-muted">/</span>
    <span>Add New Agent</span>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-person-plus me-2"></i>New Agent</div>
    <div class="card-body">
        <form method="POST" action="{{ route('agents.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-500">Agent Name <span class="text-danger">*</span></label>
                <input type="text" name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    placeholder="e.g. HASINUDDIN"
                    style="text-transform:uppercase;"
                    required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Name will be saved in uppercase.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-500">Phone Number</label>
                <input type="text" name="phone"
                    class="form-control @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}"
                    placeholder="optional">
                @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                        name="is_active" id="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="form-text">Inactive agents won't appear in the transaction form dropdown.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Agent
                </button>
                <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

{{-- Info box --}}
<div class="card mt-3" style="background:#f9fafb;border:1px solid #e8ecf0;">
    <div class="card-body py-3">
        <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">
            <i class="bi bi-info-circle me-1 text-primary"></i>What is an Agent?
        </div>
        <div style="font-size:12px;color:#6c757d;line-height:1.7;">
            Agents are the people who handle or carry transactions on behalf of Aman Traders —
            such as <strong>HASINUDDIN</strong>, <strong>ZUBAIR BHAI</strong>, etc.
            When adding a transaction, you can select the agent who handled it.
            This helps you generate agent-wise reports.
        </div>
    </div>
</div>

</div>
</div>
@endsection
