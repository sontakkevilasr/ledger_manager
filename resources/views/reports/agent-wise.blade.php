@extends('layouts.app')
@section('title','Agent Wise Report')
@section('page-title','Agent Wise Report')

@section('content')

{{-- ── Date Filter ───────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label mb-1" style="font-size:12px;">From</label>
                <input type="date" name="from" class="form-control form-control-sm" style="width:150px;" value="{{ $from }}">
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">To</label>
                <input type="date" name="to" class="form-control form-control-sm" style="width:150px;" value="{{ $to }}">
            </div>
            <button class="btn btn-primary btn-sm align-self-end">Apply</button>
            <a href="{{ route('reports.agent-wise') }}?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}"
               class="btn btn-outline-secondary btn-sm align-self-end">This Month</a>
        </form>
    </div>
</div>

@if($results->count() > 0)

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Active Agents</div>
            <div class="stat-value">{{ $results->count() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value">{{ number_format($results->sum('transaction_count')) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Total Volume</div>
            <div class="stat-value" style="font-size:18px;color:#3b5bdb;">
                ₹{{ number_format($results->sum('total_credit') + $results->sum('total_debit'), 0) }}
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- ── Bar Chart ─────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Transactions per Agent</div>
            <div class="card-body">
                <canvas id="agentChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge me-2"></i>
                Agent Summary —
                {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to
                {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Agent Name</th>
                            <th class="text-center">Transactions</th>
                            <th class="text-end" style="color:#059669;">Credit</th>
                            <th class="text-end" style="color:#dc2626;">Debit</th>
                            <th class="text-end">Total Volume</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($results as $i => $row)
                    <tr>
                        <td class="text-muted" style="font-size:11px;">{{ $i + 1 }}</td>
                        <td class="fw-500">{{ $row->name }}</td>
                        <td class="text-center">
                            <span class="badge" style="background:#eff3ff;color:#3b5bdb;font-size:12px;">
                                {{ $row->transaction_count }}
                            </span>
                        </td>
                        <td class="text-end bal-pos">₹{{ number_format($row->total_credit, 0) }}</td>
                        <td class="text-end bal-neg">₹{{ number_format($row->total_debit, 0) }}</td>
                        <td class="text-end fw-bold">
                            ₹{{ number_format($row->total_credit + $row->total_debit, 0) }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot style="background:#f9fafb;font-weight:600;">
                        <tr>
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-center">{{ $results->sum('transaction_count') }}</td>
                            <td class="text-end bal-pos">₹{{ number_format($results->sum('total_credit'), 0) }}</td>
                            <td class="text-end bal-neg">₹{{ number_format($results->sum('total_debit'), 0) }}</td>
                            <td class="text-end">
                                ₹{{ number_format($results->sum('total_credit') + $results->sum('total_debit'), 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-person-badge display-5 d-block mb-3 opacity-25"></i>
        <p class="mb-1">No agent transactions found for this period.</p>
        <small>Note: Only transactions linked to an agent are shown here.</small>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if($results->count() > 0)
<script>
new Chart(document.getElementById('agentChart'), {
    type: 'bar',
    data: {
        labels: @json($results->pluck('name')),
        datasets: [
            {
                label: 'Credit',
                data: @json($results->pluck('total_credit')),
                backgroundColor: 'rgba(5,150,105,.75)',
                borderRadius: 4,
            },
            {
                label: 'Debit',
                data: @json($results->pluck('total_debit')),
                backgroundColor: 'rgba(220,38,38,.65)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => '₹' + Number(v).toLocaleString('en-IN') }
            }
        }
    }
});
</script>
@endif
@endpush
