@extends('layouts.app')
@section('title', $customer->customer_name)
@section('page-title', $customer->customer_name)

@section('content')

{{-- ── Customer Header ─────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#eff3ff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#3b5bdb;flex-shrink:0;">
                        {{ strtoupper(substr($customer->customer_name, 0, 2)) }}
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $customer->customer_name }}</h5>
                        <div style="font-size:12px;color:#6c757d;">
                            @if($customer->mobile)<i class="bi bi-phone me-1"></i>{{ $customer->mobile }}&nbsp;@endif
                            @if($customer->city)<i class="bi bi-geo-alt me-1"></i>{{ $customer->city }}
			    @if($customer->state), {{ $customer->state }}
			    @endif
			    @endif
                        </div>
                        @if($customer->description)
                        <div style="font-size:12px;color:#6c757d;margin-top:3px;max-width:340px;">
                            <i class="bi bi-sticky me-1"></i>{{ $customer->description }}
                        </div>
                        @endif
                        <span class="badge {{ $customer->is_active ? 'badge-active' : 'badge-inactive' }} mt-1">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Always shows the TRUE all-time balance, regardless of date filter --}}
            <div class="col-md-7">
                <div class="row g-2 text-center">

                    {{-- Opening Balance from is_opening transaction --}}
                    @php $openingTxn = $customer->transactions()->where('is_opening', true)->whereNull('deleted_at')->first(); @endphp
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Opening</div>
                        @if($openingTxn)
                            <div class="fw-bold {{ $openingTxn->type === 'Debit' ? 'bal-neg' : 'bal-pos' }}" style="font-size:15px;">
                                {{ fmt_amount($openingTxn->debit > 0 ? $openingTxn->debit : $openingTxn->credit) }}
                            </div>
                            <div style="font-size:10px;" class="{{ $openingTxn->type === 'Debit' ? 'text-danger' : 'text-success' }}">
                                {{ $openingTxn->type === 'Debit' ? 'Dr' : 'Cr' }}
                            </div>
                        @else
                            <div class="bal-zero fw-bold" style="font-size:15px;">Nil</div>
                            <div style="font-size:10px;color:#6c757d;">No opening</div>
                        @endif
                    </div>

                    {{-- Total Credit (all time) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Credit</div>
                        <div class="fw-bold bal-pos" style="font-size:15px;">
                            {{ fmt_amount($customer->total_credit) }}
                        </div>
                        <div style="font-size:10px;color:#059669;">Received</div>
                    </div>

                    {{-- Total Debit (all time) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Debit</div>
                        <div class="fw-bold bal-neg" style="font-size:15px;">
                            {{ fmt_amount($customer->total_debit) }}
                        </div>
                        <div style="font-size:10px;color:#dc2626;">Billed</div>
                    </div>

                    {{-- True Balance (all time, not affected by date filter) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Balance</div>
                        @php $tb = $trueBalance; @endphp
                        <div class="fw-bold" style="font-size:15px;"
                             style="{{ $tb > 0.01 ? 'color:#dc2626' : ($tb < -0.01 ? 'color:#059669' : 'color:#6b7280') }}">
                            {{ fmt_amount(abs($tb)) }}
                        </div>
                        <div style="font-size:10px;"
                             class="{{ $tb > 0.01 ? 'text-danger' : ($tb < -0.01 ? 'text-success' : 'text-muted') }}">
                            @if($tb > 0.01) Dr — To Collect
                            @elseif($tb < -0.01) Cr — To Pay
                            @else Settled
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Action Bar ───────────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 mb-3 flex-wrap">
    @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}&type=Credit"
       class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add Credit (Payment Received)
    </a>
    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}&type=Debit"
       class="btn btn-danger btn-sm">
        <i class="bi bi-dash-circle me-1"></i>Add Debit (Bill / Goods)
    </a>
    @endif
    @if(auth()->user()->hasPermission('customers.edit') || auth()->user()->isSuperAdmin())
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    @endif
    <a href="{{ route('reports.customer-ledger') }}?customer_id={{ $customer->id }}{{ $viewAll ? '&all=1' : '&from='.$from.'&to='.$to }}"
       class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i>Print Ledger
    </a>
    <a href="{{ route('customers.index') }}" class="btn btn-link btn-sm text-muted ms-auto">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>

    @if(auth()->user()->isSuperAdmin() && config('app.allow_customer_purge'))
    <button type="button" class="btn btn-sm btn-outline-danger ms-2"
            onclick="openPurgeModal()"
            title="Permanently delete all transactions for this customer">
        <i class="bi bi-trash3 me-1"></i>Delete All Transactions
    </button>
    @endif
</div>

@if(session('purge_error'))
<div class="alert alert-danger d-flex gap-2 align-items-start mb-3" style="font-size:13px;">
    <i class="bi bi-exclamation-circle-fill mt-1 flex-shrink-0"></i>
    <div>{{ session('purge_error') }}</div>
</div>
@endif

{{-- ── Date Filter ─────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        @if($viewAll)
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge" style="background:#eff3ff;color:#3b5bdb;font-size:12px;padding:5px 10px;">
                <i class="bi bi-infinity me-1"></i>All Transactions
            </span>
            <span style="font-size:12px;color:#6c757d;">{{ $ledger->count() }} entries</span>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="bi bi-calendar-range me-1"></i>Switch to Date Filter
            </a>
        </div>
        @else
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label style="font-size:12px;font-weight:500;margin-bottom:0;">Period:</label>
            <input type="date" name="from" class="form-control form-control-sm" style="width:150px;" value="{{ $from }}">
            <span style="font-size:13px;color:#6c757d;">to</span>
            <input type="date" name="to" class="form-control form-control-sm" style="width:150px;" value="{{ $to }}">
            <button class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            <a href="{{ route('customers.show', $customer) }}?all=1" class="btn btn-outline-info btn-sm">
                <i class="bi bi-infinity me-1"></i>All Time
            </a>
            <span style="font-size:12px;color:#6c757d;margin-left:4px;">{{ $ledger->count() }} entries</span>
        </form>
        @endif
    </div>
</div>

{{-- ── Ledger Table ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-journal-text me-2"></i>Account Ledger</span>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Restore badges for hidden columns (no-print) --}}
            <span id="badge-agent" class="no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" onclick="showColumn('agent')">
                    <i class="bi bi-eye-slash me-1"></i>Agent
                </button>
            </span>
            <span id="badge-payment" class="no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" onclick="showColumn('payment')">
                    <i class="bi bi-eye-slash me-1"></i>Payment Mode
                </button>
            </span>
            @if($viewAll)
            <span style="font-size:12px;color:#6c757d;">
                <i class="bi bi-infinity me-1"></i>All Transactions
            </span>
            @else
            <span style="font-size:12px;color:#6c757d;">
                {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th>Description</th>
                    <th class="col-agent" id="th-agent" onclick="hideColumn('agent')" title="Click to hide column" style="cursor:pointer;white-space:nowrap;">
                        Agent <i class="bi bi-eye no-print" style="font-size:10px;opacity:0.5;"></i>
                    </th>
                    <th class="col-payment" id="th-payment" onclick="hideColumn('payment')" title="Click to hide column" style="cursor:pointer;white-space:nowrap;">
                        Payment <i class="bi bi-eye no-print" style="font-size:10px;opacity:0.5;"></i>
                    </th>
                    <th class="text-end" style="color:#059669;">Credit</th>
                    <th class="text-end" style="color:#dc2626;">Debit</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center" style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>

            {{-- Balance Brought Forward row (only in date-filtered view) ── --}}
            @if(!$viewAll)
            <tr style="background:#f9fafb;font-style:italic;">
                <td style="font-size:12px;color:#6c757d;">B/F</td>
                <td style="font-size:12px;color:#6c757d;">
                    Balance brought forward
                    @if($from !== now()->startOfYear()->toDateString())
                    <span style="font-size:11px;">(as of {{ \Carbon\Carbon::parse($from)->subDay()->format('d M Y') }})</span>
                    @endif
                </td>
                <td class="col-agent">—</td>
                <td class="col-payment">—</td>
                <td class="text-end">
                    @if($balanceBroughtForward < -0.01)
                    <span class="bal-pos">{{ fmt_amount(abs($balanceBroughtForward)) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end">
                    @if($balanceBroughtForward > 0.01)
                    <span class="bal-neg">{{ fmt_amount(abs($balanceBroughtForward)) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end fw-bold">
                    @php $bbf = $balanceBroughtForward; @endphp
                    <span class="{{ $bbf > 0.01 ? 'bal-neg' : ($bbf < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($bbf)) }}
                    </span>
                    <div style="font-size:10px;" class="{{ $bbf > 0.01 ? 'text-danger' : ($bbf < -0.01 ? 'text-success' : 'text-muted') }}">
                        {{ $bbf > 0.01 ? 'Dr' : ($bbf < -0.01 ? 'Cr' : 'Nil') }}
                    </div>
                </td>
                <td></td>
            </tr>
            @endif

            {{-- Transaction rows ─────────────────────────────────────────── --}}
            @forelse($ledger as $row)
            <tr class="{{ !empty($row['is_opening']) ? 'table-light' : '' }}"
                style="{{ !empty($row['is_opening']) ? 'font-style:italic;' : '' }}">
                <td style="white-space:nowrap;font-size:12px;">
                    {{ \Carbon\Carbon::parse($row['transaction_date'])->format('d M Y') }}
                </td>
                <td>
                    @if(!empty($row['is_opening']))
                    <span class="badge me-1" style="background:#eff3ff;color:#3b5bdb;font-size:9px;vertical-align:middle;">Opening</span>
                    @endif
                    {{ $row['description'] ?? '—' }}
                    @if(!empty($row['remark']))
                    <div style="font-size:11px;color:#6c757d;">{{ $row['remark'] }}</div>
                    @endif
                </td>
                <td class="col-agent" style="font-size:12px;color:#6c757d;">
                    {{ $row['agent']['name'] ?? '—' }}
                </td>
                <td class="col-payment" style="font-size:12px;color:#6c757d;">
                    {{ $row['payment_type']['payment_type'] ?? '—' }}
                </td>
                <td class="text-end">
                    @if($row['credit'] > 0)
                        <span class="bal-pos">{{ fmt_amount($row['credit']) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end">
                    @if($row['debit'] > 0)
                        <span class="bal-neg">{{ fmt_amount($row['debit']) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end fw-bold">
                    @php $rb = $row['running_balance']; @endphp
                    <span class="{{ $rb > 0.01 ? 'bal-neg' : ($rb < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($rb)) }}
                    </span>
                    <div style="font-size:10px;"
                         class="{{ $rb > 0.01 ? 'text-danger' : ($rb < -0.01 ? 'text-success' : 'text-muted') }}">
                        {{ $rb > 0.01 ? 'Dr' : ($rb < -0.01 ? 'Cr' : 'Nil') }}
                    </div>
                </td>
                <td class="text-center">
                    @if(auth()->user()->hasPermission('transactions.edit') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('transactions.edit', $row['id']) }}"
                       class="btn btn-sm btn-link p-0 text-muted">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-6 d-block mb-2 opacity-25"></i>
                    No transactions for this period.
                    @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}">Add first entry</a>
                    @endif
                </td>
            </tr>
            @endforelse

            </tbody>

            {{-- Footer: period totals + closing balance ──────────────────── --}}
            <tfoot style="background:#f9fafb;">
                <tr>
                    <td id="tfoot-period-colspan" colspan="4" class="text-end fw-bold" style="font-size:12px;">Period Total</td>
                    <td class="text-end fw-bold bal-pos">{{ fmt_amount($totalCredit) }}</td>
                    <td class="text-end fw-bold bal-neg">{{ fmt_amount($totalDebit) }}</td>
                    <td class="text-end"></td>
                    <td></td>
                </tr>
                <tr style="border-top:2px solid #e8ecf0;">
                    <td id="tfoot-closing-colspan" colspan="4" class="text-end fw-bold" style="font-size:13px;">Closing Balance</td>
                    <td colspan="2"></td>
                    <td class="text-end fw-bold">
                        @php $cb = $closingBalance; @endphp
                        <span class="{{ $cb > 0.01 ? 'bal-neg' : ($cb < -0.01 ? 'bal-pos' : 'bal-zero') }}"
                              style="font-size:14px;">
                            {{ fmt_amount(abs($cb)) }}
                        </span>
                        <div style="font-size:10px;"
                             class="{{ $cb > 0.01 ? 'text-danger' : ($cb < -0.01 ? 'text-success' : 'text-muted') }}">
                            {{ $cb > 0.01 ? 'Dr — To Collect' : ($cb < -0.01 ? 'Cr — To Pay' : 'Settled') }}
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>


{{-- ── Purge Confirmation Modal ─────────────────────────────────────────── --}}
@if(auth()->user()->isSuperAdmin() && config('app.allow_customer_purge'))
<div id="purge-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px;max-width:480px;width:calc(100% - 32px);box-shadow:0 25px 60px rgba(0,0,0,.3);">

        {{-- Header --}}
        <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:48px;height:48px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size:22px;"></i>
            </div>
            <div>
                <div style="font-size:16px;font-weight:700;color:#111827;">Purge Transaction History</div>
                <div style="font-size:12px;color:#dc2626;">Transactions deleted permanently — customer kept</div>
            </div>
        </div>

        {{-- What will be deleted --}}
        <div class="mb-4 p-3 rounded" style="background:#fef2f2;border:1px solid #fecaca;">
            <div style="font-size:12px;font-weight:600;color:#991b1b;margin-bottom:8px;">
                You are about to permanently erase all transactions for:
            </div>
            <div style="font-size:13px;color:#374151;line-height:1.9;">
                <div><i class="bi bi-check-circle-fill text-success me-2" style="font-size:11px;"></i>
                    Customer record <strong>kept:</strong> {{ $customer->customer_name }}
                </div>
                <div><i class="bi bi-x-circle-fill text-danger me-2" style="font-size:11px;"></i>
                    All <strong>{{ $customer->transactions()->withTrashed()->count() }} transactions</strong>
                </div>
                @php $bal = $customer->balance; @endphp
                @if(abs($bal) > 0.01)
                <div class="mt-2 p-2 rounded" style="background:#fff;border:1px solid #fca5a5;">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                    <strong>Outstanding balance: {{ fmt_amount(abs($bal)) }}
                    {{ $bal > 0 ? 'Dr (unpaid — you will lose this amount)' : 'Cr (you owe customer this amount)' }}
                    </strong>
                </div>
                @else
                <div><i class="bi bi-check-circle-fill text-success me-2" style="font-size:11px;"></i>
                    Balance is settled (₹0)
                </div>
                @endif
            </div>
        </div>

        {{-- Confirmation form --}}
        <form method="POST" action="{{ route('customers.purge', $customer) }}">
            @csrf
            @method('DELETE')

            <div class="mb-4">
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">
                    Type <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;color:#dc2626;">{{ $customer->customer_name }}</code> to confirm:
                </label>
                <input type="text" name="confirm_name" id="purge-confirm-input"
                    class="form-control"
                    placeholder="Type customer name exactly"
                    autocomplete="off"
                    oninput="checkPurgeName(this.value)"
                    style="border:1.5px solid #fca5a5;font-size:14px;">
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
                    Case-sensitive. Must match exactly.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" id="purge-submit-btn"
                    class="btn btn-danger flex-grow-1" disabled
                    style="font-size:14px;font-weight:600;">
                    <i class="bi bi-trash3 me-1"></i>Permanently Delete All Transactions
                </button>
                <button type="button" class="btn btn-outline-secondary px-4"
                    onclick="closePurgeModal()">
                    Cancel
                </button>
            </div>
        </form>

    </div>
</div>
@endif

@endsection

@push('styles')
<style>
#th-agent:hover, #th-payment:hover {
    background: #f0f4ff;
    color: #3b5bdb;
}
#th-agent:hover .bi-eye, #th-payment:hover .bi-eye {
    opacity: 1;
}
@media print {
    .no-print { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
const colState = { agent: false, payment: false };

document.addEventListener('DOMContentLoaded', () => {
    hideColumn('agent');
    hideColumn('payment');
});

function hideColumn(col) {
    colState[col] = false;
    document.querySelectorAll('.col-' + col).forEach(el => el.style.display = 'none');
    document.getElementById('badge-' + col).style.removeProperty('display');
    updateTfootColspan();
}

function showColumn(col) {
    colState[col] = true;
    document.querySelectorAll('.col-' + col).forEach(el => el.style.display = '');
    document.getElementById('badge-' + col).style.setProperty('display', 'none', 'important');
    updateTfootColspan();
}

function updateTfootColspan() {
    const span = 2 + (colState.agent ? 1 : 0) + (colState.payment ? 1 : 0);
    ['tfoot-period-colspan', 'tfoot-closing-colspan'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.setAttribute('colspan', span);
    });
}

const CUSTOMER_NAME = "{{ $customer->customer_name }}";

function openPurgeModal() {
    document.getElementById('purge-modal').style.display = 'flex';
    document.getElementById('purge-confirm-input').value = '';
    document.getElementById('purge-submit-btn').disabled = true;
    setTimeout(() => document.getElementById('purge-confirm-input').focus(), 100);
}

function closePurgeModal() {
    document.getElementById('purge-modal').style.display = 'none';
}

function checkPurgeName(val) {
    const btn = document.getElementById('purge-submit-btn');
    const matches = val === CUSTOMER_NAME;
    btn.disabled = !matches;
    btn.style.opacity = matches ? '1' : '0.5';
}

// Close on backdrop click
document.getElementById('purge-modal').addEventListener('click', function(e) {
    if (e.target === this) closePurgeModal();
});

</script>
@endpush
