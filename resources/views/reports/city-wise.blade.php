@extends('layouts.app')
@section('title','City Wise Report')
@section('page-title','City Wise Report')

@section('content')

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Total Cities</div>
            <div class="stat-value">{{ $results->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Total Customers</div>
            <div class="stat-value">{{ $results->sum('customer_count') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Total Credit</div>
            <div class="stat-value bal-pos" style="font-size:18px;">
                ₹{{ number_format($results->sum('total_credit'), 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Net Outstanding</div>
            {{-- outstanding: positive = Dr (to collect), negative = Cr (to pay) --}}
            @php $totalOs = $results->sum('outstanding'); @endphp
            <div class="stat-value" style="font-size:18px;"
                 class="{{ $totalOs > 0 ? 'bal-neg' : ($totalOs < 0 ? 'bal-pos' : 'bal-zero') }}">
                ₹{{ number_format(abs($totalOs), 0) }}
            </div>
            <div style="font-size:11px;"
                 class="{{ $totalOs > 0.01 ? 'text-danger' : ($totalOs < -0.01 ? 'text-success' : 'text-muted') }}">
                {{ $totalOs > 0.01 ? 'Dr — To Collect' : ($totalOs < -0.01 ? 'Cr — To Pay' : 'Settled') }}
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ── Chart ─────────────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Outstanding by City</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="cityChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-geo-alt me-2"></i>{{ $results->count() }} Cities</span>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>City</th>
                            <th>State</th>
                            <th class="text-center">Customers</th>
                            <th class="text-end" style="color:#059669;">Credit</th>
                            <th class="text-end" style="color:#dc2626;">Debit</th>
                            <th class="text-end">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($results as $i => $row)
                    {{-- outstanding: positive = Dr (to collect), negative = Cr (to pay) --}}
                    @php $os = $row->outstanding; @endphp
                    <tr>
                        <td class="text-muted" style="font-size:11px;">{{ $i + 1 }}</td>
                        <td class="fw-500">{{ $row->city ?? 'Unknown' }}</td>
                        <td style="font-size:12px;color:#6c757d;">{{ $row->state ?? '—' }}</td>
                        <td class="text-center">{{ $row->customer_count }}</td>
                        <td class="text-end bal-pos">₹{{ number_format($row->total_credit, 0) }}</td>
                        <td class="text-end bal-neg">₹{{ number_format($row->total_debit, 0) }}</td>
                        <td class="text-end">
                            <span class="fw-bold {{ $os > 0.01 ? 'bal-neg' : ($os < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                                ₹{{ number_format(abs($os), 0) }}
                            </span>
                            <div style="font-size:10px;"
                                 class="{{ $os > 0.01 ? 'text-danger' : ($os < -0.01 ? 'text-success' : 'text-muted') }}">
                                {{ $os > 0.01 ? 'Dr' : ($os < -0.01 ? 'Cr' : 'Nil') }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No data found</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot style="background:#f9fafb;font-weight:600;">
                        <tr>
                            <td colspan="3" class="text-end" style="font-size:12px;">Total</td>
                            <td class="text-center">{{ $results->sum('customer_count') }}</td>
                            <td class="text-end bal-pos">₹{{ number_format($results->sum('total_credit'), 0) }}</td>
                            <td class="text-end bal-neg">₹{{ number_format($results->sum('total_debit'), 0) }}</td>
                            @php $footOs = $results->sum('outstanding'); @endphp
                            <td class="text-end fw-bold {{ $footOs > 0.01 ? 'bal-neg' : ($footOs < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                                ₹{{ number_format(abs($footOs), 0) }}
                                <div style="font-size:10px;">
                                    {{ $footOs > 0.01 ? 'Dr' : ($footOs < -0.01 ? 'Cr' : '') }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Only plot cities with positive Dr outstanding for the chart
const allLabels  = @json($results->pluck('city'));
const allData    = @json($results->pluck('outstanding'));
const colors = ['#3b5bdb','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d','#ea580c','#0284c7'];

// Filter to only Dr balances (positive) for the donut chart
const chartLabels = [];
const chartData   = [];
const chartColors = [];
allLabels.forEach((label, i) => {
    if (allData[i] > 0) {
        chartLabels.push(label);
        chartData.push(allData[i]);
        chartColors.push(colors[chartColors.length % colors.length]);
    }
});

if (chartData.length > 0) {
    new Chart(document.getElementById('cityChart'), {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { font:{ size:11 }, boxWidth:10, padding:8 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ₹' + Number(ctx.raw).toLocaleString('en-IN') + ' Dr'
                    }
                }
            }
        }
    });
} else {
    document.getElementById('cityChart').parentElement.innerHTML =
        '<p class="text-muted text-center py-4" style="font-size:13px;">No outstanding Dr balances to chart.</p>';
}
</script>
@endpush

@push('styles')
<style>
@media print {
    #sidebar, #topbar, .btn { display: none !important; }
    #main { margin-left: 0 !important; }
    canvas { display: none; }
    .col-lg-5 { display: none; }
    .col-lg-7 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
}
</style>
@endpush
