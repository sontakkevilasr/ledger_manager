@extends('layouts.app')
@section('title','Transactions')
@section('page-title','All Transactions')

@section('content')
@php
    $sortUrl  = fn(string $col) => request()->fullUrlWithQuery(['sort_by' => $col, 'sort_dir' => ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc', 'page' => 1]);
    $sortIcon = fn(string $col) => $sortBy === $col ? ($sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up opacity-25';
@endphp

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
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:12px;">Description</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search description…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:12px;">Amount</label>
                <input type="number" name="amount" class="form-control form-control-sm" placeholder="Exact amount" step="0.01" min="0" value="{{ request('amount') }}">
            </div>
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <div class="col-md-1">
                <button class="btn btn-primary btn-sm w-100">Search</button>
            </div>
            @if(request()->hasAny(['customer_id','type','agent_id','from','to','search','amount']))
            <div class="col-md-1">
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
            </div>
            @endif
            @php $exportQuery = http_build_query(request()->only(['customer_id','type','agent_id','from','to','search','amount','sort_by','sort_dir'])); @endphp
            <div class="col-auto d-flex gap-1">
                <a id="export-excel-btn"
                   href="{{ route('transactions.export', 'excel') }}?{{ $exportQuery }}"
                   class="btn btn-success btn-sm" title="Export to Excel">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a id="export-pdf-btn"
                   href="{{ route('transactions.export', 'pdf') }}?{{ $exportQuery }}"
                   class="btn btn-danger btn-sm" title="Export to PDF">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
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
            <div style="font-size:16px;font-weight:700;color:#059669;">{{ fmt_amount($summary->total_credit) }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center py-2">
            <div style="font-size:11px;color:#6c757d;">Total Debit</div>
            <div style="font-size:16px;font-weight:700;color:#dc2626;">{{ fmt_amount($summary->total_debit) }}</div>
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-arrow-left-right me-2"></i>{{ $transactions->total() }} Transactions</span>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span id="badge-agent" class="no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" onclick="showColumn('agent')">
                    <i class="bi bi-eye-slash me-1"></i>Agent
                </button>
            </span>
            <span id="badge-payment" class="no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" onclick="showColumn('payment')">
                    <i class="bi bi-eye-slash me-1"></i>Payment
                </button>
            </span>
            <span id="badge-by" class="no-print">
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:11px;" onclick="showColumn('by')">
                    <i class="bi bi-eye-slash me-1"></i>By
                </button>
            </span>
            @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
            <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus me-1"></i>Add Entry
            </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><a href="{{ $sortUrl('transaction_date') }}" class="text-decoration-none text-dark">Date <i class="bi {{ $sortIcon('transaction_date') }}"></i></a></th>
                    <th>Customer</th>
                    <th>Description</th>
                    <th class="col-agent" id="th-agent" onclick="hideColumn('agent')" title="Click to hide" style="cursor:pointer;white-space:nowrap;">
                        Agent <i class="bi bi-eye no-print" style="font-size:10px;opacity:0.5;"></i>
                    </th>
                    <th class="col-payment" id="th-payment" onclick="hideColumn('payment')" title="Click to hide" style="cursor:pointer;white-space:nowrap;">
                        Payment <i class="bi bi-eye no-print" style="font-size:10px;opacity:0.5;"></i>
                    </th>
                    <th class="text-center"><a href="{{ $sortUrl('type') }}" class="text-decoration-none text-dark">Type <i class="bi {{ $sortIcon('type') }}"></i></a></th>
                    <th class="text-end"><a href="{{ $sortUrl('credit') }}" class="text-decoration-none text-dark">Credit <i class="bi {{ $sortIcon('credit') }}"></i></a></th>
                    <th class="text-end"><a href="{{ $sortUrl('debit') }}" class="text-decoration-none text-dark">Debit <i class="bi {{ $sortIcon('debit') }}"></i></a></th>
                    <th class="col-by" id="th-by" onclick="hideColumn('by')" title="Click to hide" style="cursor:pointer;white-space:nowrap;">
                        By <i class="bi bi-eye no-print" style="font-size:10px;opacity:0.5;"></i>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($transactions as $t)
            <tr class="{{ $t->type === 'Credit' ? 'tr-credit' : 'tr-debit' }}">
                <td style="white-space:nowrap;font-size:12px;">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('customers.show',$t->customer_id) }}" class="text-decoration-none" style="font-size:13px;">
                        {{ $t->customer?->customer_name ?? '—' }}
                    </a>
                </td>
                <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    {{ $t->description ?? '—' }}
                </td>
                <td class="col-agent" style="font-size:12px;color:#6c757d;">{{ $t->agent?->name ?? $t->remark ?? '—' }}</td>
                <td class="col-payment" style="font-size:12px;color:#6c757d;">{{ $t->paymentType?->payment_type ?? '—' }}</td>
                <td class="text-center">
                    <span class="badge {{ $t->type==='Credit' ? 'badge-credit' : 'badge-debit' }}" style="font-size:10px;">
                        {{ $t->type }}
                    </span>
                </td>
                <td class="text-end">
                    @if($t->credit > 0)<span class="bal-pos">{{ fmt_amount($t->credit) }}</span>
                    @else <span class="text-muted">—</span>@endif
                </td>
                <td class="text-end">
                    @if($t->debit > 0)<span class="bal-neg">{{ fmt_amount($t->debit) }}</span>
                    @else <span class="text-muted">—</span>@endif
                </td>
                <td class="col-by" style="font-size:11px;color:#6c757d;">{{ $t->createdBy?->name ?? 'Import' }}</td>
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

@push('styles')
<style>
#th-agent:hover, #th-payment:hover, #th-by:hover {
    background: #f0f4ff;
    color: #3b5bdb;
}
#th-agent:hover .bi-eye, #th-payment:hover .bi-eye,
#th-by:hover .bi-eye { opacity: 1; }
@media print {
    .no-print { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
const colState = { agent: false, payment: false, by: false };

document.addEventListener('DOMContentLoaded', () => {
    ['agent', 'payment', 'by'].forEach(hideColumn);
});

function hideColumn(col) {
    colState[col] = false;
    document.querySelectorAll('.col-' + col).forEach(el => el.style.display = 'none');
    document.getElementById('badge-' + col).style.removeProperty('display');
    updateExportUrls();
}

function showColumn(col) {
    colState[col] = true;
    document.querySelectorAll('.col-' + col).forEach(el => el.style.display = '');
    document.getElementById('badge-' + col).style.setProperty('display', 'none', 'important');
    updateExportUrls();
}

function updateExportUrls() {
    const visible = Object.keys(colState).filter(c => colState[c]);
    const colParam = visible.length ? '&cols=' + visible.join(',') : '';
    ['export-excel-btn', 'export-pdf-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const base = el.href.split('&cols=')[0];
        el.href = base + colParam;
    });
}
</script>
@endpush
