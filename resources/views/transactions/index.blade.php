@extends('layouts.app')
@section('title','Transactions')
@section('page-title','All Transactions')

@section('content')

{{-- ── Filters ──────────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All Customers</option>
                    @foreach($customers as $id => $name)
                    <option value="{{ $id }}" {{ request('customer_id')==$id ? 'selected':'' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type')=='credit' ? 'selected':'' }}>Credit</option>
                    <option value="debit"  {{ request('type')=='debit'  ? 'selected':'' }}>Debit</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $id => $name)
                    <option value="{{ $id }}" {{ request('agent_id')==$id ? 'selected':'' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary bar ──────────────────────────────────────────────────────── --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card text-center py-2">
            <div style="font-size:11px;color:#6c757d;">Entries</div>
            <div style="font-size:16px;font-weight:700;">{{ number_format($summary->total_count) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center py-2">
            <div style="font-size:11px;color:#6c757d;">Total Credit</div>
            <div style="font-size:16px;font-weight:700;color:#059669;">₹{{ number_format($summary->total_credit,0) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center py-2">
            <div style="font-size:11px;color:#6c757d;">Total Debit</div>
            <div style="font-size:16px;font-weight:700;color:#dc2626;">₹{{ number_format($summary->total_debit,0) }}</div>
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-arrow-left-right me-2"></i>{{ $transactions->total() }} Transactions</span>
        @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
        <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus me-1"></i>Add Entry
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Description</th>
                    <th>Agent</th>
                    <th>Payment</th>
                    <th class="text-center">Type</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($transactions as $t)
            <tr>
                <td style="white-space:nowrap;font-size:12px;">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('customers.show',$t->customer_id) }}" class="text-decoration-none" style="font-size:13px;">
                        {{ $t->customer?->customer_name ?? '—' }}
                    </a>
                </td>
                <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $t->description ?? '—' }}
                </td>
                <td style="font-size:12px;color:#6c757d;">{{ $t->agent?->name ?? $t->remark ?? '—' }}</td>
                <td style="font-size:12px;color:#6c757d;">{{ $t->paymentType?->payment_type ?? '—' }}</td>
                <td class="text-center">
                    <span class="badge {{ $t->type==='Credit' ? 'badge-credit' : 'badge-debit' }}" style="font-size:10px;">
                        {{ $t->type }}
                    </span>
                </td>
                <td class="text-end">
                    @if($t->credit > 0)<span class="bal-pos">₹{{ number_format($t->credit,2) }}</span>
                    @else <span class="text-muted">—</span>@endif
                </td>
                <td class="text-end">
                    @if($t->debit > 0)<span class="bal-neg">₹{{ number_format($t->debit,2) }}</span>
                    @else <span class="text-muted">—</span>@endif
                </td>
                <td style="font-size:11px;color:#6c757d;">{{ $t->createdBy?->name ?? 'Import' }}</td>
                <td>
                    @if(auth()->user()->hasPermission('transactions.edit') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('transactions.edit',$t) }}" class="btn btn-sm btn-link p-0 text-muted"><i class="bi bi-pencil"></i></a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-6 d-block mb-2 opacity-25"></i>
                    No transactions found.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white">{{ $transactions->links() }}</div>
    @endif
</div>

@endsection
