@extends('layouts.app')
@section('title','Customer Ledger')
@section('page-title','Customer Ledger Report')

@section('content')

{{-- ── Filter Form ───────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:12px;">Customer <span class="text-danger">*</span></label>
                <select name="customer_id" class="form-select" required>
                    <option value="">— Select Customer —</option>
                    @foreach($customers as $id => $name)
                    <option value="{{ $id }}" {{ request('customer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>View Ledger</button>
            </div>
            @if($customer)
            <div class="col-md-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
            @endif
        </form>
    </div>
</div>

@if($customer)

{{-- ── Customer Header (printable) ──────────────────────────────────────── --}}
<div class="card mb-3" id="print-header">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-1 fw-bold">{{ $customer->customer_name }}</h5>
                <div style="font-size:12px;color:#6c757d;">
                    @if($customer->mobile)<i class="bi bi-phone me-1"></i>{{ $customer->mobile }}&nbsp;&nbsp;@endif
                    @if($customer->phone && $customer->phone !== $customer->mobile)<i class="bi bi-telephone me-1"></i>{{ $customer->phone }}&nbsp;&nbsp;@endif
                    @if($customer->city)<i class="bi bi-geo-alt me-1"></i>{{ $customer->city }}, {{ $customer->state }}@endif
                </div>
                @if($customer->address)
                <div style="font-size:12px;color:#6c757d;">{{ $customer->address }}</div>
                @endif
            </div>
            <div class="col-md-6 text-md-end">
                <div style="font-size:12px;color:#6c757d;">Statement Period</div>
                <div style="font-weight:600;">
                    {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                </div>
                <div style="font-size:12px;color:#6c757d;margin-top:4px;">
                    Printed on {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Summary Row ──────────────────────────────────────────────────────── --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="stat-card text-center py-3">
            <div class="stat-label">Opening Balance</div>
            <div class="stat-value" style="font-size:18px;">₹{{ number_format($customer->opening_balance, 2) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card text-center py-3">
            <div class="stat-label">Total Credit</div>
            <div class="stat-value" style="font-size:18px;color:#059669;">₹{{ number_format($ledger->sum('credit'), 2) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card text-center py-3">
            <div class="stat-label">Total Debit</div>
            <div class="stat-value" style="font-size:18px;color:#dc2626;">₹{{ number_format($ledger->sum('debit'), 2) }}</div>
        </div>
    </div>
</div>

{{-- ── Ledger Table ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-text me-2"></i>Account Statement — {{ $ledger->count() }} entries</span>
        <span style="font-size:12px;color:#6c757d;">Closing Balance:
            <strong class="{{ $runningBalance > 0 ? 'bal-pos' : ($runningBalance < 0 ? 'bal-neg' : 'bal-zero') }}">
                ₹{{ number_format(abs($runningBalance), 2) }}
            </strong>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0" id="ledger-table">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th>Description</th>
                    <th>Agent</th>
                    <th>Payment Mode</th>
                    <th class="text-end" style="color:#059669;">Credit</th>
                    <th class="text-end" style="color:#dc2626;">Debit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>

            {{-- Opening balance row --}}
            @if($customer->opening_balance != 0)
            <tr style="background:#fffbeb;">
                <td style="font-size:12px;color:#6c757d;">Opening</td>
                <td><em style="color:#6c757d;font-size:12px;">Opening / Carry-forward Balance</em></td>
                <td>—</td><td>—</td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end fw-bold">₹{{ number_format($customer->opening_balance, 2) }}</td>
            </tr>
            @endif

            @forelse($ledger as $row)
            <tr>
                <td style="font-size:12px;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($row['transaction_date'])->format('d M Y') }}
                </td>
                <td>
                    {{ $row['description'] ?? '—' }}
                    @if(!empty($row['remark']))
                    <span style="font-size:11px;color:#6c757d;"> — {{ $row['remark'] }}</span>
                    @endif
                </td>
                <td style="font-size:12px;color:#6c757d;">
                    {{ $row['agent']['name'] ?? '—' }}
                </td>
                <td style="font-size:12px;color:#6c757d;">
                    {{ $row['payment_type']['payment_type'] ?? '—' }}
                </td>
                <td class="text-end">
                    @if($row['credit'] > 0)
                        <span class="bal-pos">₹{{ number_format($row['credit'], 2) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-end">
                    @if($row['debit'] > 0)
                        <span class="bal-neg">₹{{ number_format($row['debit'], 2) }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-end fw-bold">
                    <span class="{{ $row['running_balance'] > 0.01 ? 'bal-pos' : ($row['running_balance'] < -0.01 ? 'bal-neg' : 'bal-zero') }}">
                        ₹{{ number_format(abs($row['running_balance']), 2) }}
                    </span>
                    <div style="font-size:9px;color:#9ca3af;">
                        {{ $row['running_balance'] > 0.01 ? 'Dr' : ($row['running_balance'] < -0.01 ? 'Cr' : '') }}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-6 d-block mb-2 opacity-25"></i>
                    No transactions found for this period.
                </td>
            </tr>
            @endforelse
            </tbody>

            @if($ledger->count() > 0)
            <tfoot style="background:#f9fafb;">
                <tr>
                    <td colspan="4" class="text-end fw-bold" style="font-size:13px;">Closing Balance</td>
                    <td class="text-end fw-bold bal-pos">₹{{ number_format($ledger->sum('credit'), 2) }}</td>
                    <td class="text-end fw-bold bal-neg">₹{{ number_format($ledger->sum('debit'), 2) }}</td>
                    <td class="text-end fw-bold">
                        <span class="{{ $runningBalance > 0.01 ? 'bal-pos' : ($runningBalance < -0.01 ? 'bal-neg' : 'bal-zero') }}">
                            ₹{{ number_format(abs($runningBalance), 2) }}
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-person-lines-fill display-5 d-block mb-3 opacity-25"></i>
        <p class="mb-1">Select a customer above to view their full ledger.</p>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
@media print {
    #sidebar, #topbar, form, .btn { display: none !important; }
    #main { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 12px !important; }
    .card-header { background: #f8f9fa !important; }
    body { font-size: 12px !important; }
    .stat-card { border: 1px solid #dee2e6 !important; }
}
</style>
@endpush
