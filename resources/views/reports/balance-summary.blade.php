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
        <a href="{{ route('reports.balance-summary.export', request()->only(['filter','city','search'])) }}"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>City</th>
                    <th>Mobile</th>
                    <th class="text-end">Opening Bal.</th>
                    <th class="text-end" style="color:#059669;">Total Credit</th>
                    <th class="text-end" style="color:#dc2626;">Total Debit</th>
                    <th class="text-end">Net Balance</th>
                    <th class="text-center">Actions</th>
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
                <td style="color:#6c757d;font-size:12px;">{{ $c->city }}</td>
                <td style="font-size:12px;">{{ $c->mobile ?: '—' }}</td>

                {{-- Opening balance with Dr/Cr label --}}
                <td class="text-end" style="font-size:12px;">
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

                <td class="text-end bal-pos">{{ fmt_amount($c->total_credit) }}</td>
                <td class="text-end bal-neg">{{ fmt_amount($c->total_debit) }}</td>

                {{-- Net balance: positive = Dr = To Collect, negative = Cr = To Pay --}}
                <td class="text-end">
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
                <td class="text-center">
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
                    <td colspan="4" class="text-end fw-bold" style="font-size:12px;">Page Total</td>
                    <td class="text-end fw-bold bal-pos">{{ fmt_amount($customers->sum('total_credit')) }}</td>
                    <td class="text-end fw-bold bal-neg">{{ fmt_amount($customers->sum('total_debit')) }}</td>
                    @php $pageNet = $customers->sum('net_balance'); @endphp
                    <td class="text-end fw-bold {{ $pageNet > 0.01 ? 'bal-neg' : ($pageNet < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        {{ fmt_amount(abs($pageNet)) }}
                        <div style="font-size:10px;">
                            {{ $pageNet > 0.01 ? 'Dr' : ($pageNet < -0.01 ? 'Cr' : '') }}
                        </div>
                    </td>
                    <td></td>
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

@endsection
