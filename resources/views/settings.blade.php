@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

@if(session('success'))
<div class="alert alert-success d-flex gap-2 align-items-start mb-3" style="font-size:13px;">
    <i class="bi bi-check-circle-fill mt-1 flex-shrink-0"></i>{{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('settings.update') }}">
@csrf @method('PUT')

{{-- ── Display Settings ─────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-display text-primary"></i>
        <span>Display Settings</span>
    </div>
    <div class="card-body">

        {{-- Scale Amounts toggle --}}
        <div class="d-flex align-items-start justify-content-between gap-4 pb-4 border-bottom">
            <div style="flex:1;">
                <div style="font-size:14px;font-weight:600;color:#111827;margin-bottom:4px;">
                    Scale Amount Display
                </div>
                <div style="font-size:13px;color:#6b7280;line-height:1.6;">
                    {{ $settings['scale_amounts']->description }}
                </div>

                {{-- Live example --}}
                <div class="mt-3 p-3 rounded" style="background:#f9fafb;border:1px solid #e5e7eb;">
                    <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                        Example
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <div style="font-size:11px;color:#9ca3af;">Stored in database</div>
                            <div style="font-size:16px;font-weight:700;color:#111827;font-family:monospace;">145000</div>
                        </div>
                        <i class="bi bi-arrow-right" style="color:#9ca3af;"></i>
                        <div id="example-off" style="{{ $settings['scale_amounts']->value === '1' ? 'display:none' : '' }}">
                            <div style="font-size:11px;color:#9ca3af;">Displayed (OFF)</div>
                            <div style="font-size:16px;font-weight:700;color:#374151;">145000</div>
                        </div>
                        <div id="example-on" style="{{ $settings['scale_amounts']->value === '1' ? '' : 'display:none' }}">
                            <div style="font-size:11px;color:#059669;">Displayed (ON — ÷100)</div>
                            <div style="font-size:16px;font-weight:700;color:#059669;">₹1,450.00</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 pt-1">
                <div class="form-check form-switch" style="transform:scale(1.4);transform-origin:right center;">
                    <input class="form-check-input" type="checkbox"
                        name="scale_amounts"
                        id="scale_amounts"
                        {{ $settings['scale_amounts']->value === '1' ? 'checked' : '' }}
                        onchange="toggleExample(this.checked)">
                </div>
            </div>
        </div>

        {{-- Current status banner --}}
        <div class="mt-3">
            <div id="status-off" class="d-flex align-items-center gap-2"
                 style="{{ $settings['scale_amounts']->value === '1' ? 'display:none!important' : '' }}">
                <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:11px;">
                    <i class="bi bi-toggle-off me-1"></i>Currently OFF
                </span>
                <span style="font-size:12px;color:#6b7280;">Amounts shown as stored plain integer (e.g. 145000)</span>
            </div>
            <div id="status-on" class="d-flex align-items-center gap-2"
                 style="{{ $settings['scale_amounts']->value === '1' ? '' : 'display:none' }}">
                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:11px;">
                    <i class="bi bi-toggle-on me-1"></i>Currently ON
                </span>
                <span style="font-size:12px;color:#059669;">Amounts divided by 100 for display (e.g. ₹1,450.00)</span>
            </div>
        </div>

    </div>
</div>

{{-- ── Company Settings ─────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-building text-primary"></i>
        <span>Company Settings</span>
    </div>
    <div class="card-body">

        <div class="mb-4">
            <label class="form-label fw-500">
                {{ $settings['company_name']->label }}
            </label>
            <input type="text" name="company_name" class="form-control"
                value="{{ $settings['company_name']->value }}"
                placeholder="Aman Traders">
            <div class="form-text">{{ $settings['company_name']->description }}</div>
        </div>

        <div class="mb-2">
            <label class="form-label fw-500">
                {{ $settings['financial_year_start']->label }}
            </label>
            <div class="d-flex align-items-center gap-2">
                <select name="financial_year_start" class="form-select" style="width:200px;">
                    @php $fyStart = $settings['financial_year_start']->value; @endphp
                    <option value="04-01" {{ $fyStart === '04-01' ? 'selected' : '' }}>April 1 (Indian FY)</option>
                    <option value="01-01" {{ $fyStart === '01-01' ? 'selected' : '' }}>January 1 (Calendar Year)</option>
                    <option value="07-01" {{ $fyStart === '07-01' ? 'selected' : '' }}>July 1</option>
                    <option value="10-01" {{ $fyStart === '10-01' ? 'selected' : '' }}>October 1</option>
                </select>
            </div>
            <div class="form-text">{{ $settings['financial_year_start']->description }}</div>
        </div>

    </div>
</div>

{{-- ── Save button ──────────────────────────────────────────────────────── --}}
<div class="d-flex gap-3 align-items-center">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Save Settings
    </button>
    <span style="font-size:12px;color:#9ca3af;">Changes take effect immediately for all users.</span>
</div>

</form>
</div>
</div>
@endsection

@push('scripts')
<script>
function toggleExample(isOn) {
    document.getElementById('example-on').style.display  = isOn ? '' : 'none';
    document.getElementById('example-off').style.display = isOn ? 'none' : '';
    document.getElementById('status-on').style.display   = isOn ? '' : 'none';
    document.getElementById('status-off').style.display  = isOn ? 'none' : '';
}
</script>
@endpush
