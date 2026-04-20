@extends('layouts.app')
@section('title','Dashboard')
@section('page-title','Dashboard')

@section('content')

{{-- ── Summary Cards ─────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#eff3ff;"><i class="bi bi-people" style="color:#3b5bdb;"></i></div>
            <div>
                <div class="stat-label">Total Customers</div>
                <div class="stat-value">{{ number_format($totalCustomers) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#f0fdf4;"><i class="bi bi-arrow-down-circle" style="color:#059669;"></i></div>
            <div>
                <div class="stat-label">Total Credit</div>
                <div class="stat-value" style="color:#059669;font-size:18px;">{{ fmt_amount($totalCredit) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fff5f5;"><i class="bi bi-arrow-up-circle" style="color:#dc2626;"></i></div>
            <div>
                <div class="stat-label">Total Debit</div>
                <div class="stat-value" style="color:#dc2626;font-size:18px;">{{ fmt_amount($totalDebit) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fffbeb;"><i class="bi bi-wallet2" style="color:#d97706;"></i></div>
            <div>
                <div class="stat-label">Outstanding</div>
                <div class="stat-value" style="color:#d97706;font-size:18px;">{{ fmt_amount(abs($totalOutstanding)) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── This month mini stats ──────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row text-center g-0">
            <div class="col">
                <div style="font-size:11px;color:#6c757d;">This Month Entries</div>
                <div style="font-size:18px;font-weight:700;">{{ number_format($thisMonth->count ?? 0) }}</div>
            </div>
            <div class="col border-start">
                <div style="font-size:11px;color:#6c757d;">This Month Credit</div>
                <div style="font-size:18px;font-weight:700;color:#059669;">{{ fmt_amount($thisMonth->credit ?? 0) }}</div>
            </div>
            <div class="col border-start">
                <div style="font-size:11px;color:#6c757d;">This Month Debit</div>
                <div style="font-size:18px;font-weight:700;color:#dc2626;">{{ fmt_amount($thisMonth->debit ?? 0) }}</div>
            </div>
            <div class="col border-start">
                <div style="font-size:11px;color:#6c757d;">This Month Net</div>
                @php $net = ($thisMonth->credit ?? 0) - ($thisMonth->debit ?? 0); @endphp
                <div style="font-size:18px;font-weight:700;color:{{ $net >= 0 ? '#059669' : '#dc2626' }};">
                    {{ fmt_amount(abs($net)) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts row ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-bar-chart me-2 text-primary"></i>Monthly Credit vs Debit</span>
                <span style="font-size:11px;color:#6c757d;">Last 12 months</span>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2 text-primary"></i>City-wise Outstanding</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="cityChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Top Debtors + Recent Transactions ─────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Top Outstanding Customers</span>
                <a href="{{ route('reports.balance-summary') }}" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Customer</th><th>City</th><th class="text-end">Outstanding</th></tr></thead>
                    <tbody>
                    @forelse($topDebtors as $c)
                    <tr>
                        <td>
                            <a href="{{ route('customers.show', $c->id) }}" class="text-decoration-none fw-500">
                                {{ $c->customer_name }}
                            </a>
                        </td>
                        <td style="color:#6c757d;">{{ $c->city }}</td>
                        <td class="text-end bal-neg">{{ fmt_amount($c->outstanding) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No outstanding balances</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-arrow-left-right me-2 text-primary"></i>Recent Transactions</span>
                <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Customer</th><th>Date</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse($recentTransactions as $t)
                    <tr>
                        <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <a href="{{ route('customers.show',$t->customer_id) }}" class="text-decoration-none">
                                {{ $t->customer?->customer_name ?? '—' }}
                            </a>
                        </td>
                        <td style="color:#6c757d;white-space:nowrap;">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M') }}</td>
                        <td>
                            <span class="badge {{ $t->type==='Credit' ? 'badge-credit' : 'badge-debit' }}" style="font-size:10px;">
                                {{ $t->type }}
                            </span>
                        </td>
                        <td class="text-end {{ $t->type==='Credit' ? 'bal-pos' : 'bal-neg' }}">
                            {{ fmt_amount($t->credit + $t->debit) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No transactions yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Recent Activity Log ─────────────────────────────────── --}}
@if(auth()->user()->hasPermission('logs.view') || auth()->user()->isSuperAdmin())
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>Recent Activity</span>
        <a href="{{ route('reports.activity-logs') }}" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Record</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($recentActivity as $log)
            <tr>
                <td>{{ $log->user?->name ?? 'System' }}</td>
                <td>
                    @php
                        $ac=['created'=>'success','updated'=>'primary','deleted'=>'danger','viewed'=>'secondary','exported'=>'info','login'=>'success','logout'=>'warning'];
                        $col=$ac[$log->action] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $col }}-subtle text-{{ $col }} border border-{{ $col }}-subtle" style="font-size:10px;">{{ $log->action }}</span>
                </td>
                <td style="color:#6c757d;">{{ $log->module }}</td>
                <td style="font-size:12px;">{{ $log->record_label }}</td>
                <td style="color:#6c757d;white-space:nowrap;font-size:12px;">{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No activity yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// Divisor for chart display — matches PHP fmt_amount() scaling
const DIVISOR = {{ scale_divisor() }};

function fmtChart(v) {
    return '₹' + (v / DIVISOR).toLocaleString('en-IN', { minimumFractionDigits: 0 });
}

// ── Monthly Bar Chart ─────────────────────────────────────
const months = @json($monthly->pluck('month'));
const credits = @json($monthly->pluck('total_credit'));
const debits  = @json($monthly->pluck('total_debit'));

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: months.map(m => { const d=new Date(m+'-01'); return d.toLocaleString('default',{month:'short',year:'2-digit'}); }),
        datasets: [
            { label:'Credit', data: credits.map(v => v/DIVISOR), backgroundColor:'rgba(5,150,105,.75)', borderRadius:4, borderSkipped:false },
            { label:'Debit',  data: debits.map(v  => v/DIVISOR), backgroundColor:'rgba(220,38,38,.65)',  borderRadius:4, borderSkipped:false },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position:'top', labels:{ font:{size:12}, boxWidth:12 } } },
        scales: { y: { beginAtZero:true, ticks:{ callback: v => fmtChart(v * DIVISOR) } } }
    }
});

// ── City Donut Chart ──────────────────────────────────────
const cities = @json($cityWise->pluck('city'));
const outst  = @json($cityWise->pluck('outstanding'));

new Chart(document.getElementById('cityChart'), {
    type: 'doughnut',
    data: {
        labels: cities,
        datasets: [{ data: outst.map(v => v/DIVISOR),
            backgroundColor: ['#3b5bdb','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d'],
            borderWidth:2, borderColor:'#fff'
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:11}, boxWidth:10, padding:8 } },
            tooltip: { callbacks: { label: ctx => ' ' + fmtChart(ctx.raw * DIVISOR) } }
        }
    }
});
</script>
@endpush
