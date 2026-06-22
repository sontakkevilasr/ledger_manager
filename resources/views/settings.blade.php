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


{{-- ── Danger Zone ──────────────────────────────────────────────────────── --}}
<div class="card mb-4" style="border:1.5px solid #fee2e2;">
    <div class="card-header d-flex align-items-center gap-2" style="background:#fff5f5;border-bottom:1px solid #fee2e2;">
        <i class="bi bi-exclamation-octagon-fill text-danger"></i>
        <span class="fw-600" style="color:#991b1b;">Danger Zone</span>
        <span class="ms-auto badge" style="background:#fee2e2;color:#991b1b;font-size:11px;">
            Irreversible Actions
        </span>
    </div>
    <div class="card-body">

        {{-- ── Allow Transaction Edit / Delete ────────────────────────── --}}
        <div class="d-flex align-items-start justify-content-between gap-4 mb-4 pb-4" style="border-bottom:1px solid #fee2e2;">
            <div style="flex:1;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:14px;font-weight:600;color:#111827;">
                        Allow Edit &amp; Delete Transactions
                    </span>
                    @if(isset($settings['allow_transaction_edit']) && $settings['allow_transaction_edit']->value === '1')
                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:10px;">
                        <i class="bi bi-unlock-fill me-1"></i>Currently ENABLED
                    </span>
                    @else
                    <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:10px;">
                        <i class="bi bi-lock-fill me-1"></i>Currently DISABLED
                    </span>
                    @endif
                </div>

                <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:12px;">
                    When <strong>ON</strong> (default): Users with permission can edit and delete existing transactions.
                    A customer-name confirmation is required before any change is saved.<br>
                    When <strong>OFF</strong>: No one — including Super Admin — can edit or delete existing transactions.
                    The Edit page becomes read-only. Use this to lock the books at period close.
                </div>

                <div class="p-3 rounded" style="background:#eff3ff;border:1px solid #c7d2fe;">
                    <div style="font-size:11px;font-weight:700;color:#3730a3;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">
                        Safeguards when ENABLED
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                            <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                            <div><strong>Permission-gated</strong> — Only users with the <code>transactions.edit</code> or <code>transactions.delete</code> permission see the buttons</div>
                        </div>
                        <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                            <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                            <div><strong>Customer name confirmation</strong> — User must type the exact customer name before the action proceeds</div>
                        </div>
                        <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                            <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                            <div><strong>Audit trail</strong> — Every edit is logged with old and new values for accountability</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 pt-1">
                <div class="form-check form-switch" style="transform:scale(1.4);transform-origin:right center;">
                    <input class="form-check-input" type="checkbox"
                        name="allow_transaction_edit"
                        id="allow_transaction_edit"
                        {{ isset($settings['allow_transaction_edit']) && $settings['allow_transaction_edit']->value === '1' ? 'checked' : '' }}>
                </div>
            </div>
        </div>

        {{-- ── Allow Customer Purge ─────────────────────────────────────── --}}
        <div class="d-flex align-items-start justify-content-between gap-4">
            <div style="flex:1;">

                {{-- Title + badge --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:14px;font-weight:600;color:#111827;">
                        Allow Permanent Transaction Purge
                    </span>
                    @if($settings['allow_customer_purge']->value === '1')
                    <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:10px;">
                        <i class="bi bi-unlock-fill me-1"></i>Currently ENABLED
                    </span>
                    @else
                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:10px;">
                        <i class="bi bi-lock-fill me-1"></i>Currently DISABLED
                    </span>
                    @endif
                </div>

                {{-- What it does --}}
                <div style="font-size:13px;color:#374151;line-height:1.7;margin-bottom:16px;">
                    When enabled, the Super Admin can permanently erase all transaction history
                    for a customer while keeping the customer record intact.
                    This is designed for situations where a business relationship has
                    ended and transaction history must be removed for privacy reasons.
                </div>

                {{-- Education block --}}
                <div id="purge-education" style="{{ $settings['allow_customer_purge']->value === '1' ? '' : '' }}">

                    {{-- What gets deleted --}}
                    <div class="mb-3 p-3 rounded" style="background:#fafafa;border:1px solid #e5e7eb;">
                        <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                            What gets permanently deleted
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                                <div><strong>Customer record</strong> — <span style="color:#059669;font-weight:600;">Kept intact</span> — Name, contact, city, all profile information is preserved</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
                                <div><strong>All transactions</strong> — Every debit, credit and opening balance entry ever recorded is permanently removed</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x-circle-fill text-danger mt-1 flex-shrink-0"></i>
                                <div><strong>Running balances</strong> — All ledger history, balance calculations, reports for this customer</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#059669;">
                                <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                                <div><strong>Audit log is kept</strong> — A record of who performed the purge, when, and how many transactions were deleted is permanently preserved</div>
                            </div>
                        </div>
                    </div>

                    {{-- What cannot be undone --}}
                    <div class="mb-3 p-3 rounded" style="background:#fff7ed;border:1px solid #fed7aa;">
                        <div class="d-flex gap-2 align-items-start">
                            <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
                            <div>
                                <div style="font-size:12px;font-weight:600;color:#92400e;margin-bottom:6px;">
                                    This action cannot be undone — ever
                                </div>
                                <div style="font-size:12px;color:#78350f;line-height:1.7;">
                                    Unlike deactivating a customer (which hides them but preserves all data),
                                    a purge is a hard, permanent database delete.
                                    No backup is created. No recovery is possible.
                                    Once confirmed, the data is gone.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- When it should be used --}}
                    <div class="mb-3 p-3 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div style="font-size:11px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                            When this is appropriate
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-check2 text-success mt-1 flex-shrink-0"></i>
                                <div>Business relationship has permanently ended and the customer has no future dealings</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-check2 text-success mt-1 flex-shrink-0"></i>
                                <div>Customer has explicitly requested removal of their data</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-check2 text-success mt-1 flex-shrink-0"></i>
                                <div>Account was created in error and has no real transaction history</div>
                            </div>
                        </div>
                    </div>

                    {{-- When it should NOT be used --}}
                    <div class="mb-3 p-3 rounded" style="background:#fef2f2;border:1px solid #fecaca;">
                        <div style="font-size:11px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                            When NOT to use this — use Deactivate instead
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x text-danger mt-1 flex-shrink-0"></i>
                                <div>Customer still has an outstanding balance — recovering the amount becomes impossible after purge</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x text-danger mt-1 flex-shrink-0"></i>
                                <div>Customer is temporarily inactive or seasonal — they may return next year</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x text-danger mt-1 flex-shrink-0"></i>
                                <div>You want to clean up the customer list — use the Inactive status filter instead</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-x text-danger mt-1 flex-shrink-0"></i>
                                <div>There is any chance you may need transaction history for disputes, audits, or tax purposes</div>
                            </div>
                        </div>
                    </div>

                    {{-- Safeguards already in place --}}
                    <div class="p-3 rounded" style="background:#eff3ff;border:1px solid #c7d2fe;">
                        <div style="font-size:11px;font-weight:700;color:#3730a3;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">
                            Safeguards built into the purge action
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                                <div><strong>Super Admin only</strong> — No other role can see or trigger the purge button</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                                <div><strong>Name confirmation required</strong> — Admin must type the exact customer name before the button activates</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                                <div><strong>Outstanding balance shown</strong> — If the customer has any unpaid balance, it is displayed prominently in the confirmation dialog</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                                <div><strong>Permanent audit entry</strong> — Who purged, when, how many records deleted — this log survives forever</div>
                            </div>
                            <div class="d-flex align-items-start gap-2" style="font-size:12px;color:#374151;">
                                <i class="bi bi-shield-check text-primary mt-1 flex-shrink-0"></i>
                                <div><strong>This setting itself</strong> — Purge is disabled by default. It must be consciously enabled here before it becomes available</div>
                            </div>
                        </div>
                    </div>

                </div>{{-- end purge-education --}}

            </div>

            {{-- Toggle --}}
            <div class="flex-shrink-0 pt-1">
                <div class="form-check form-switch" style="transform:scale(1.4);transform-origin:right center;">
                    <input class="form-check-input" type="checkbox"
                        name="allow_customer_purge"
                        id="allow_customer_purge"
                        {{ $settings['allow_customer_purge']->value === '1' ? 'checked' : '' }}
                        onchange="togglePurgeWarning(this.checked)">
                </div>
            </div>

        </div>

        {{-- Live warning when toggled ON --}}
        <div id="purge-live-warning" class="mt-3"
             style="{{ $settings['allow_customer_purge']->value === '1' ? '' : 'display:none;' }}">
            <div class="p-3 rounded d-flex gap-3 align-items-start"
                 style="background:#fef2f2;border:1.5px solid #fca5a5;">
                <i class="bi bi-exclamation-octagon-fill text-danger mt-1 flex-shrink-0" style="font-size:18px;"></i>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#991b1b;margin-bottom:4px;">
                        Purge is currently ENABLED
                    </div>
                    <div style="font-size:12px;color:#7f1d1d;line-height:1.7;">
                        The permanent delete button is now visible on every customer's ledger page.
                        Treat this setting like a loaded weapon — disable it again after use.
                        Do not leave it enabled permanently.
                    </div>
                </div>
            </div>
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

function togglePurgeWarning(isOn) {
    const warn    = document.getElementById('purge-live-warning');
    const toggleEl = document.getElementById('allow_customer_purge');

    warn.style.display = isOn ? '' : 'none';

    // Visual feedback on the toggle itself
    if (isOn) {
        toggleEl.style.accentColor = '#dc2626';
    } else {
        toggleEl.style.accentColor = '';
    }
}

// Apply red accent on load if purge is already enabled
document.addEventListener('DOMContentLoaded', function () {
    const toggleEl = document.getElementById('allow_customer_purge');
    if (toggleEl && toggleEl.checked) {
        toggleEl.style.accentColor = '#dc2626';
    }
});
</script>
@endpush
