@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')

<div class="row g-4">

{{-- ── Left column: Profile info + change password ─────────────────────── --}}
<div class="col-lg-5">

    {{-- Profile card --}}
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <div style="width:72px;height:72px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#3b5bdb;margin:0 auto 14px;">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <h5 class="mb-1 fw-bold">{{ $user->name }}</h5>
            <div style="font-size:13px;color:#6c757d;">{{ $user->email }}</div>
            <div class="mt-2">
                @foreach($user->roles as $role)
                <span class="badge" style="background:#eff3ff;color:#3b5bdb;font-size:12px;">
                    {{ $role->display_name }}
                </span>
                @endforeach
            </div>
            <div class="mt-3 pt-3 border-top" style="font-size:12px;color:#6c757d;">
                <div>Username: <strong>{{ $user->username }}</strong></div>
                <div class="mt-1">Last login:
                    <strong>{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'N/A' }}</strong>
                </div>
                <div class="mt-1">Member since:
                    <strong>{{ $user->created_at->format('d M Y') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Success / error flash --}}
    @if(session('success'))
    <div class="alert alert-success d-flex gap-2 align-items-start mb-3" style="font-size:13px;">
        <i class="bi bi-check-circle-fill mt-1 flex-shrink-0"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- ── Update profile form ───────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-person-gear me-2"></i>Update Profile</div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-500">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-500">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" name="username"
                            class="form-control @error('username') is-invalid @enderror"
                            value="{{ old('username', $user->username) }}" required>
                    </div>
                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-500">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i>Update Profile
                </button>
            </form>
        </div>
    </div>

    {{-- ── Change password form ───────────────────────────────── --}}
    <div class="card" id="password-card">
        <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-500">Current Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="cur_pwd" name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            placeholder="Enter current password" required>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="toggleField('cur_pwd','cur_eye')">
                            <i class="bi bi-eye" id="cur_eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                    <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-500">New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" id="new_pwd" name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Min 8 characters" required>
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="toggleField('new_pwd','new_eye')">
                            <i class="bi bi-eye" id="new_eye"></i>
                        </button>
                    </div>
                    @error('password')
                    <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-500">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation"
                        class="form-control"
                        placeholder="Repeat new password" required>
                </div>

                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-lock me-1"></i>Change Password
                </button>
            </form>
        </div>
    </div>

</div>

{{-- ── Right column: Login history + recent activity ──────────────────── --}}
<div class="col-lg-7">

    {{-- Login history --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-arrow-in-right me-2"></i>Login History</span>
            <span style="font-size:11px;color:#6c757d;">Last 10 sessions</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th class="text-center">Status</th>
                        <th>IP Address</th>
                        <th>Device</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($loginHistory as $log)
                <tr>
                    <td style="font-size:12px;white-space:nowrap;">
                        {{ $log->created_at->format('d M Y') }}<br>
                        <span style="color:#6c757d;">{{ $log->created_at->format('h:i A') }}</span>
                    </td>
                    <td class="text-center">
                        @if($log->status === 'success')
                            <span class="badge" style="background:#d1fae5;color:#065f46;font-size:10px;">
                                <i class="bi bi-check-circle me-1"></i>Success
                            </span>
                        @elseif($log->status === 'logout')
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:10px;">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:10px;">
                                <i class="bi bi-x-circle me-1"></i>Failed
                            </span>
                        @endif
                    </td>
                    <td style="font-size:12px;font-family:var(--bs-font-monospace);">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td style="font-size:11px;color:#6c757d;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $log->user_agent }}">
                        {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 40) : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted" style="font-size:13px;">
                        No login history found.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-2"></i>My Recent Activity</span>
            <a href="{{ route('reports.activity-logs') }}?user_id={{ $user->id }}"
               class="btn btn-sm btn-outline-secondary" style="font-size:11px;">View All</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Record</th>
                    </tr>
                </thead>
                <tbody>
                @php
                $actionColors = [
                    'created'   => 'success', 'updated'  => 'primary',
                    'deleted'   => 'danger',  'viewed'   => 'secondary',
                    'exported'  => 'info',    'login'    => 'success',
                    'logout'    => 'warning',
                ];
                @endphp
                @forelse($recentActivity as $log)
                <tr>
                    <td style="font-size:11px;color:#6c757d;white-space:nowrap;">
                        {{ $log->created_at->format('d M') }}<br>
                        {{ $log->created_at->format('h:i A') }}
                    </td>
                    <td>
                        @php $col = $actionColors[$log->action] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $col }}-subtle text-{{ $col }} border border-{{ $col }}-subtle"
                              style="font-size:10px;">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td style="font-size:12px;">{{ ucfirst($log->module) }}</td>
                    <td style="font-size:12px;color:#6c757d;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $log->record_label ?? $log->description ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted" style="font-size:13px;">
                        No activity recorded yet.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

@endsection

@push('scripts')
<script>
function toggleField(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    if (f.type === 'password') {
        f.type = 'text';
        i.className = 'bi bi-eye-slash';
    } else {
        f.type = 'password';
        i.className = 'bi bi-eye';
    }
}

// If there's a password error, scroll to password card
@if(session('tab') === 'password' || $errors->has('current_password') || $errors->has('password'))
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('password-card')
        .scrollIntoView({ behavior: 'smooth', block: 'start' });
});
@endif
</script>
@endpush
