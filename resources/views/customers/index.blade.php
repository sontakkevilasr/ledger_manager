@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@section('content')

{{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <form method="GET" class="d-flex gap-2 flex-wrap flex-grow-1">
        <input type="text" name="search" class="form-control" style="max-width:260px;"
            placeholder="Search name, mobile, city…" value="{{ request('search') }}">
        <select name="status" class="form-select" style="width:140px;">
            <option value="">All Status</option>
            <option value="active"   {{ request('status')=='active'   ? 'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
        <select name="city" class="form-select" style="width:160px;">
            <option value="">All Cities</option>
            @foreach($cities as $city)
            <option value="{{ $city }}" {{ request('city')==$city ? 'selected':'' }}>{{ $city }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Search</button>
        @if(request()->anyFilled(['search','status','city']))
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </form>

    @can('customers.create')
    <a href="{{ route('customers.create') }}" class="btn btn-primary ms-auto">
        <i class="bi bi-person-plus me-1"></i>Add Customer
    </a>
    @endcan
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-people me-2"></i>{{ $customers->total() }} Customer{{ $customers->total()!=1?'s':'' }}</span>
        <a href="{{ route('reports.balance-summary') }}" class="btn btn-sm btn-outline-primary" style="font-size:12px;">
            <i class="bi bi-bar-chart me-1"></i>Balance Report
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>City</th>
                    <th class="text-center">Transactions</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $c)
            @php
                $balance = $c->opening_balance
                    + \App\Models\Transaction::where('customer_id',$c->id)->sum('credit')
                    - \App\Models\Transaction::where('customer_id',$c->id)->sum('debit');
            @endphp
            <tr>
                <td class="text-muted" style="font-size:11px;">{{ $c->id }}</td>
                <td>
                    <a href="{{ route('customers.show',$c) }}" class="text-decoration-none fw-500">
                        {{ $c->customer_name }}
                    </a>
                    @if($c->email)
                    <div style="font-size:11px;color:#6c757d;">{{ $c->email }}</div>
                    @endif
                </td>
                <td>{{ $c->mobile ?: $c->phone ?: '—' }}</td>
                <td>{{ $c->city ?? '—' }}</td>
                <td class="text-center">
                    <span style="font-size:12px;">{{ $c->transactions_count }}</span>
                </td>
                <td class="text-end">
                    @if($balance > 0.01)
                        <span class="bal-pos">₹{{ number_format(abs($balance),2) }}</span>
                        <div style="font-size:10px;color:#6c757d;">To collect</div>
                    @elseif($balance < -0.01)
                        <span class="bal-neg">₹{{ number_format(abs($balance),2) }}</span>
                        <div style="font-size:10px;color:#6c757d;">To pay</div>
                    @else
                        <span class="bal-zero">₹0.00</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge {{ $c->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $c->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="{{ route('customers.show',$c) }}" class="btn btn-sm btn-outline-primary" title="View Ledger">
                            <i class="bi bi-journal-text"></i>
                        </a>
                        @if(auth()->user()->hasPermission('customers.edit') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('customers.edit',$c) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                        @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('transactions.create','?customer_id='.$c->id) }}" class="btn btn-sm btn-outline-success" title="Add Entry">
                            <i class="bi bi-plus"></i>
                        </a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-people display-6 d-block mb-2 opacity-25"></i>
                    No customers found.
                    @if(request()->anyFilled(['search','status','city']))
                        <a href="{{ route('customers.index') }}">Clear filters</a>
                    @endif
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white border-top-0 pt-0">
        {{ $customers->links() }}
    </div>
    @endif
</div>

@endsection
