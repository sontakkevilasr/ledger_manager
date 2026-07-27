@extends('layouts.app')
@section('title','Balance Summary')
@section('page-title','Balance Summary Report')

@section('content')

{{-- ── Filters ──────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" style="width:200px;"
                    placeholder="Customer name…" value="{{ request('search') }}">
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Filter</label>
                <select name="filter" class="form-select form-select-sm" style="width:180px;">
                    <option value="all"    {{ $filter=='all'    ? 'selected':'' }}>All Customers</option>
                    <option value="debit"  {{ $filter=='debit'  ? 'selected':'' }}>Dr — To Collect</option>
                    <option value="credit" {{ $filter=='credit' ? 'selected':'' }}>Cr — To Pay</option>
                    <option value="zero"   {{ $filter=='zero'   ? 'selected':'' }}>Settled (zero)</option>
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">City</label>
                <select name="city" class="form-select form-select-sm" style="width:160px;">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                    <option value="{{ $city }}" {{ request('city')==$city ? 'selected':'' }}>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-1 align-self-end">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="{{ route('reports.balance-summary') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- ── Grand Totals ──────────────────────────────────────────────────────── --}}
{{-- These now match the Dashboard outstanding card exactly --}}
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Grand Total Credit</div>
            <div class="stat-value" style="color:#059669;font-size:20px;">
                {{ fmt_amount($totals->grand_credit) }}
            </div>
            <div style="font-size:11px;color:#6c757d;">Payments received</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Grand Total Debit</div>
            <div class="stat-value" style="color:#dc2626;font-size:20px;">
                {{ fmt_amount($totals->grand_debit) }}
            </div>
            <div style="font-size:11px;color:#6c757d;">Bills / goods sent</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <div class="stat-label">Net Outstanding</div>
            {{-- grandOutstanding: positive = Dr (to collect), negative = Cr (to pay) --}}
            @php $go = $grandOutstanding; @endphp
            <div class="stat-value" style="font-size:20px;"
                 style="{{ $go > 0 ? 'color:#dc2626' : ($go < 0 ? 'color:#059669' : 'color:#6b7280') }}">
                {{ fmt_amount(abs($go)) }}
            </div>
            <div style="font-size:11px;"
                 class="{{ $go > 0.01 ? 'text-danger' : ($go < -0.01 ? 'text-success' : 'text-muted') }}">
                @if($go > 0.01) Dr — To Collect (matches dashboard)
                @elseif($go < -0.01) Cr — To Pay
                @else All Settled
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Customer Table ───────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart-line me-2"></i>{{ $customers->total() }} Customers</span>
        @if(auth()->user()->hasPermission('reports.export') || auth()->user()->isSuperAdmin())
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#printColsModal">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="{{ route('reports.balance-summary.export', request()->only(['filter','city','search'])) }}"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </a>
        </div>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="col-city">City</th>
                    <th class="col-mobile">Mobile</th>
                    <th class="text-end col-opening">Opening Bal.</th>
                    <th class="text-end col-credit" style="color:#059669;">Total Credit</th>
                    <th class="text-end col-debit" style="color:#dc2626;">Total Debit</th>
                    <th class="text-end col-net">Net Balance</th>
                    <th class="text-center col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $c)
            {{-- net_balance: positive = Dr (to collect), negative = Cr (to pay) --}}
            @php $bal = $c->net_balance; @endphp
            <tr>
                <td>
                    <a href="{{ route('customers.show', $c->id) }}" class="text-decoration-none fw-500">
                        {{ $c->customer_name }}
                    </a>
                </td>
                <td class="col-city" style="color:#6c757d;font-size:12px;">{{ $c->city }}</td>
                <td class="col-mobile" style="font-size:12px;">{{ $c->mobile ?: '—' }}</td>

                {{-- Opening balance with Dr/Cr label --}}
                <td class="text-end col-opening" style="font-size:12px;">
                    @if($c->opening_balance > 0)
                        <span class="{{ $c->opening_balance_type === 'Dr' ? 'bal-neg' : 'bal-pos' }}">
                            {{ fmt_amount($c->opening_balance) }}
                        </span>
                        <span style="font-size:10px;margin-left:2px;"
                              class="{{ $c->opening_balance_type === 'Dr' ? 'text-danger' : 'text-success' }}">
                            {{ $c->opening_balance_type }}
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td class="text-end col-credit bal-pos">{{ fmt_amount($c->total_credit) }}</td>
                <td class="text-end col-debit bal-neg">{{ fmt_amount($c->total_debit) }}</td>

                {{-- Net balance: positive = Dr = To Collect, negative = Cr = To Pay --}}
                <td class="text-end col-net">
                    <span class="fw-bold {{ $bal > 0.01 ? 'bal-neg' : ($bal < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($bal)) }}
                    </span>
                    <div style="font-size:10px;"
                         class="{{ $bal > 0.01 ? 'text-danger' : ($bal < -0.01 ? 'text-success' : 'text-muted') }}">
                        @if($bal > 0.01) Dr — To Collect
                        @elseif($bal < -0.01) Cr — To Pay
                        @else Settled
                        @endif
                    </div>
                </td>
                <td class="text-center col-actions">
                    <a href="{{ route('customers.show', $c->id) }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:11px;">
                        <i class="bi bi-journal-text"></i> Ledger
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-5 text-muted">No customers found.</td></tr>
            @endforelse
            </tbody>

            {{-- Footer totals --}}
            @if($customers->count() > 0)
            <tfoot style="background:#f9fafb;">
                <tr>
                    <td class="fw-bold" style="font-size:12px;">Page Total ({{ $customers->count() }})</td>
                    <td class="col-city"></td>
                    <td class="col-mobile"></td>
                    <td class="col-opening"></td>
                    <td class="text-end fw-bold col-credit bal-pos">{{ fmt_amount($customers->sum('total_credit')) }}</td>
                    <td class="text-end fw-bold col-debit bal-neg">{{ fmt_amount($customers->sum('total_debit')) }}</td>
                    @php $pageNet = $customers->sum('net_balance'); @endphp
                    <td class="text-end fw-bold col-net {{ $pageNet > 0.01 ? 'bal-neg' : ($pageNet < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($pageNet)) }}
                        <div style="font-size:10px;">
                            {{ $pageNet > 0.01 ? 'Dr' : ($pageNet < -0.01 ? 'Cr' : '') }}
                        </div>
                    </td>
                    <td class="col-actions"></td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white">{{ $customers->links() }}</div>
    @endif
</div>

{{-- ── Print column picker ─────────────────────────────────────────────── --}}
<div class="modal fade" id="printColsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-printer me-1"></i>Choose columns to print</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" style="font-size:12px;">Customer name is always printed. Uncheck any columns you don't want on the printout.</p>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="city" id="pcCity">
                    <label class="form-check-label" for="pcCity">City</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="mobile" id="pcMobile">
                    <label class="form-check-label" for="pcMobile">Mobile</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="opening" id="pcOpening" checked>
                    <label class="form-check-label" for="pcOpening">Opening Balance</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="credit" id="pcCredit" checked>
                    <label class="form-check-label" for="pcCredit">Total Credit</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="debit" id="pcDebit" checked>
                    <label class="form-check-label" for="pcDebit">Total Debit</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input print-col-check" type="checkbox" value="net" id="pcNet" checked>
                    <label class="form-check-label" for="pcNet">Net Balance</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnDoPrint">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
@media screen {
    .col-city, .col-mobile { display: none !important; }
}
@media print {
    #sidebar, #topbar, .btn, form, .card-footer { display: none !important; }
    #main { margin-left: 0 !important; }
    .card { border: none !important; }
    .col-actions { display: none !important; }
    body.hide-city   .col-city,
    body.hide-mobile  .col-mobile,
    body.hide-opening .col-opening,
    body.hide-credit  .col-credit,
    body.hide-debit   .col-debit,
    body.hide-net     .col-net { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const optionalCols = ['city', 'mobile', 'opening', 'credit', 'debit', 'net'];

    document.getElementById('btnDoPrint').addEventListener('click', function () {
        const checked = new Set(
            Array.from(document.querySelectorAll('.print-col-check:checked')).map(cb => cb.value)
        );

        optionalCols.forEach(col => {
            document.body.classList.toggle('hide-' + col, !checked.has(col));
        });

        const modalEl = document.getElementById('printColsModal');
        bootstrap.Modal.getInstance(modalEl)?.hide();

        setTimeout(() => window.print(), 300);
    });

    window.addEventListener('afterprint', function () {
        optionalCols.forEach(col => document.body.classList.remove('hide-' + col));
    });
})();
</script>
@endpush
