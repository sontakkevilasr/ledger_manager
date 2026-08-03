@extends('layouts.app')
@section('title','Date Range Report')
@section('page-title','Date Range Report')

@section('content')

{{-- ── Filter Form ───────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">From Date <span class="text-danger">*</span></label>
                <input type="date" name="from" class="form-control" value="{{ $from }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">To Date <span class="text-danger">*</span></label>
                <input type="date" name="to" class="form-control" value="{{ $to }}" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Generate
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.date-range') }}?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}"
                   class="btn btn-outline-secondary w-100">This Month</a>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.date-range') }}?from={{ now()->startOfYear()->toDateString() }}&to={{ now()->toDateString() }}"
                   class="btn btn-outline-secondary w-100">This Year</a>
            </div>
        </form>
    </div>
</div>

@if($results->count() > 0)

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Customers</div>
            <div class="stat-value" style="font-size:20px;">{{ $summary['count'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Total Credit</div>
            <div class="stat-value" style="font-size:20px;color:#059669;">{{ fmt_amount($summary['total_credit']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Total Debit</div>
            <div class="stat-value" style="font-size:20px;color:#dc2626;">{{ fmt_amount($summary['total_debit']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Closing Balance</div>
            @php $net = $summary['total_closing']; @endphp
            <div class="stat-value" style="font-size:20px;" class="{{ $net > 0.01 ? 'bal-neg' : ($net < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                {{ fmt_amount(abs($net)) }}
            </div>
            <div style="font-size:10px;color:#6c757d;">
                {{ $net > 0.01 ? 'Dr — To Collect' : ($net < -0.01 ? 'Cr — To Pay' : 'Settled') }}
            </div>
        </div>
    </div>
</div>

{{-- ── Results Table ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <i class="bi bi-calendar-range me-2"></i>
            Transactions from
            <strong>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</strong>
            to
            <strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong>
        </span>
        @if(auth()->user()->hasPermission('reports.export') || auth()->user()->isSuperAdmin())
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>City</th>
                    <th>Mobile</th>
                    <th class="text-end">Opening Bal.</th>
                    <th class="text-end" style="color:#059669;">Credit</th>
                    <th class="text-end" style="color:#dc2626;">Debit</th>
                    <th class="text-end">Closing Balance</th>
                    <th class="text-center">View</th>
                </tr>
            </thead>
            <tbody>
            @foreach($results as $i => $row)
            <tr>
                <td class="text-muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td>
                    <a href="{{ route('reports.customer-ledger') }}?customer_id={{ $row->id }}&from={{ $from }}&to={{ $to }}"
                       class="text-decoration-none fw-500">
                        {{ $row->customer_name }}
                    </a>
                </td>
                <td style="font-size:12px;color:#6c757d;">{{ $row->city ?? '—' }}</td>
                <td style="font-size:12px;">{{ $row->mobile ?? '—' }}</td>
                <td class="text-end" style="font-size:12px;">
                    @php $ob = $row->opening_balance; @endphp
                    <span class="{{ $ob > 0.01 ? 'bal-neg' : ($ob < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($ob)) }}
                    </span>
                    <span style="font-size:10px;" class="{{ $ob > 0.01 ? 'text-danger' : ($ob < -0.01 ? 'text-success' : 'text-muted') }}">
                        {{ $ob > 0.01 ? 'Dr' : ($ob < -0.01 ? 'Cr' : '') }}
                    </span>
                </td>
                <td class="text-end bal-pos">{{ fmt_amount($row->credit) }}</td>
                <td class="text-end bal-neg">{{ fmt_amount($row->debit) }}</td>
                <td class="text-end">
                    @php $bal = $row->closing_balance; @endphp
                    <span class="fw-bold {{ $bal > 0.01 ? 'bal-neg' : ($bal < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($bal)) }}
                    </span>
                    <div style="font-size:10px;color:#6c757d;">
                        {{ $bal > 0.01 ? 'Dr — To Collect' : ($bal < -0.01 ? 'Cr — To Pay' : 'Settled') }}
                    </div>
                </td>
                <td class="text-center">
                    <a href="{{ route('reports.customer-ledger') }}?customer_id={{ $row->id }}&from={{ $from }}&to={{ $to }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:11px;" title="View how this balance was calculated">
                        <i class="bi bi-journal-text"></i>
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot style="background:#f9fafb;font-weight:600;">
                <tr>
                    <td colspan="4" class="text-end" style="font-size:13px;">Grand Total</td>
                    <td class="text-end">
                        @php $tob = $summary['total_opening']; @endphp
                        <span class="{{ $tob > 0.01 ? 'bal-neg' : ($tob < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                            {{ fmt_amount(abs($tob)) }}
                        </span>
                    </td>
                    <td class="text-end bal-pos">{{ fmt_amount($summary['total_credit']) }}</td>
                    <td class="text-end bal-neg">{{ fmt_amount($summary['total_debit']) }}</td>
                    <td class="text-end">
                        @php $tcl = $summary['total_closing']; @endphp
                        <span class="{{ $tcl > 0.01 ? 'bal-neg' : ($tcl < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                            {{ fmt_amount(abs($tcl)) }}
                        </span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-calendar-x display-5 d-block mb-3 opacity-25"></i>
        <p class="mb-1">No transactions found for the selected date range.</p>
        <small>Try a different date range above.</small>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .btn, form { display: none !important; }
    #main { margin-left: 0 !important; }
    .card { border: none !important; }
}
</style>
@endpush
