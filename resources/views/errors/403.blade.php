@extends('layouts.app')
@section('title','Access Denied')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height:60vh;">
    <div class="text-center">
        <div style="font-size:72px;font-weight:800;color:#e8ecf0;line-height:1;">403</div>
        <h4 class="mt-2 mb-2">Access Denied</h4>
        <p class="text-muted mb-4">You don't have permission to access this page.<br>Contact your administrator if you think this is a mistake.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>
@endsection
