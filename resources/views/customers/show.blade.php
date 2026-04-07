@extends('layouts.app')
@section('title', $customer->customer_name)
@section('page-title', $customer->customer_name)

@section('content')

{{-- ── Customer Header ─────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:50px;height:50px;background:#eff3ff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#3b5bdb;flex-shrink:0;">
                        {{ strtoupper(substr($customer->customer_name, 0, 2)) }}
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $customer->customer_name }}</h5>
                        <div style="font-size:12px;color:#6c757d;">
                            @if($customer->mobile)<i class="bi bi-phone me-1"></i>{{ $customer->mobile }}&nbsp;@endif
                            @if($customer->city)<i class="bi bi-geo-alt me-1"></i>{{ $customer->city }} 
                            @if($customer->state), {{ $customer->state }}
                            @endif
                            @endif
                        </div>
                        <span class="badge {{ $customer->is_active ? 'badge-active' : 'badge-inactive' }} mt-1">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Always shows the TRUE all-time balance, regardless of date filter --}}
            <div class="col-md-7">
                <div class="row g-2 text-center">

                    {{-- Opening Balance --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Opening</div>
                        @if($customer->opening_balance > 0)
                            <div class="fw-bold" style="font-size:15px;"
                                class="{{ $customer->opening_balance_type === 'Dr' ? 'bal-neg' : 'bal-pos' }}">
                                ₹{{ number_format($customer->opening_balance, 2) }}
                            </div>
                            <div style="font-size:10px;"
                                class="{{ $customer->opening_balance_type === 'Dr' ? 'text-danger' : 'text-success' }}">
                                {{ $customer->opening_balance_type }}
                            </div>
                        @else
                            <div class="bal-zero fw-bold" style="font-size:15px;">₹0.00</div>
                            <div style="font-size:10px;color:#6c757d;">Nil</div>
                        @endif
                    </div>

                    {{-- Total Credit (all time) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Credit</div>
                        <div class="fw-bold bal-pos" style="font-size:15px;">
                            ₹{{ number_format($customer->total_credit, 2) }}
                        </div>
                        <div style="font-size:10px;color:#059669;">Received</div>
                    </div>

                    {{-- Total Debit (all time) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Debit</div>
                        <div class="fw-bold bal-neg" style="font-size:15px;">
                            ₹{{ number_format($customer->total_debit, 2) }}
                        </div>
                        <div style="font-size:10px;color:#dc2626;">Billed</div>
                    </div>

                    {{-- True Balance (all time, not affected by date filter) --}}
                    <div class="col-3">
                        <div style="font-size:10px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Balance</div>
                        @php $tb = $trueBalance; @endphp
                        <div class="fw-bold" style="font-size:15px;"
                             style="{{ $tb > 0.01 ? 'color:#dc2626' : ($tb < -0.01 ? 'color:#059669' : 'color:#6b7280') }}">
                            ₹{{ number_format(abs($tb), 2) }}
                        </div>
                        <div style="font-size:10px;"
                             class="{{ $tb > 0.01 ? 'text-danger' : ($tb < -0.01 ? 'text-success' : 'text-muted') }}">
                            @if($tb > 0.01) Dr — To Collect
                            @elseif($tb < -0.01) Cr — To Pay
                            @else Settled
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Action Bar ───────────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 mb-3 flex-wrap">
    @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}&type=Credit"
       class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add Credit (Payment Received)
    </a>
    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}&type=Debit"
       class="btn btn-danger btn-sm">
        <i class="bi bi-dash-circle me-1"></i>Add Debit (Bill / Goods)
    </a>
    @endif
    @if(auth()->user()->hasPermission('customers.edit') || auth()->user()->isSuperAdmin())
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    @endif
    <a href="{{ route('reports.customer-ledger') }}?customer_id={{ $customer->id }}&from={{ $from }}&to={{ $to }}"
       class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i>Print Ledger
    </a>
    <a href="{{ route('customers.index') }}" class="btn btn-link btn-sm text-muted ms-auto">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

{{-- ── Date Filter ─────────────────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label style="font-size:12px;font-weight:500;margin-bottom:0;">Period:</label>
            <input type="date" name="from" class="form-control form-control-sm" style="width:150px;" value="{{ $from }}">
            <span style="font-size:13px;color:#6c757d;">to</span>
            <input type="date" name="to" class="form-control form-control-sm" style="width:150px;" value="{{ $to }}">
            <button class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            <span style="font-size:12px;color:#6c757d;margin-left:4px;">{{ $ledger->count() }} entries</span>
        </form>
    </div>
</div>

{{-- ── Ledger Table ─────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-text me-2"></i>Account Ledger</span>
        <span style="font-size:12px;color:#6c757d;">
            {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th>Description</th>
                    <th>Agent</th>
                    <th>Payment</th>
                    <th class="text-end" style="color:#059669;">Credit</th>
                    <th class="text-end" style="color:#dc2626;">Debit</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center" style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>

            {{-- Balance Brought Forward row ─────────────────────────────── --}}
            {{-- This is the correct balance at the START of the filtered period --}}
            <tr style="background:#f9fafb;font-style:italic;">
                <td style="font-size:12px;color:#6c757d;">B/F</td>
                <td style="font-size:12px;color:#6c757d;">
                    Balance brought forward
                    @if($from !== now()->startOfYear()->toDateString())
                    <span style="font-size:11px;">(as of {{ \Carbon\Carbon::parse($from)->subDay()->format('d M Y') }})</span>
                    @endif
                </td>
                <td>—</td>
                <td>—</td>
                <td class="text-end">
                    @if($balanceBroughtForward < -0.01)
                    <span class="bal-pos">₹{{ number_format(abs($balanceBroughtForward), 2) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end">
                    @if($balanceBroughtForward > 0.01)
                    <span class="bal-neg">₹{{ number_format(abs($balanceBroughtForward), 2) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end fw-bold">
                    @php $bbf = $balanceBroughtForward; @endphp
                    <span class="{{ $bbf > 0.01 ? 'bal-neg' : ($bbf < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        ₹{{ number_format(abs($bbf), 2) }}
                    </span>
                    <div style="font-size:10px;" class="{{ $bbf > 0.01 ? 'text-danger' : ($bbf < -0.01 ? 'text-success' : 'text-muted') }}">
                        {{ $bbf > 0.01 ? 'Dr' : ($bbf < -0.01 ? 'Cr' : 'Nil') }}
                    </div>
                </td>
                <td></td>
            </tr>

            {{-- Transaction rows ─────────────────────────────────────────── --}}
            @forelse($ledger as $row)
            <tr>
                <td style="white-space:nowrap;font-size:12px;">
                    {{ \Carbon\Carbon::parse($row['transaction_date'])->format('d M Y') }}
                </td>
                <td>
                    {{ $row['description'] ?? '—' }}
                    @if(!empty($row['remark']))
                    <div style="font-size:11px;color:#6c757d;">{{ $row['remark'] }}</div>
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
                    @else —
                    @endif
                </td>
                <td class="text-end">
                    @if($row['debit'] > 0)
                        <span class="bal-neg">₹{{ number_format($row['debit'], 2) }}</span>
                    @else —
                    @endif
                </td>
                <td class="text-end fw-bold">
                    @php $rb = $row['running_balance']; @endphp
                    <span class="{{ $rb > 0.01 ? 'bal-neg' : ($rb < -0.01 ? 'bal-pos' : 'bal-zero') }}">
                        ₹{{ number_format(abs($rb), 2) }}
                    </span>
                    <div style="font-size:10px;"
                         class="{{ $rb > 0.01 ? 'text-danger' : ($rb < -0.01 ? 'text-success' : 'text-muted') }}">
                        {{ $rb > 0.01 ? 'Dr' : ($rb < -0.01 ? 'Cr' : 'Nil') }}
                    </div>
                </td>
                <td class="text-center">
                    @if(auth()->user()->hasPermission('transactions.edit') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('transactions.edit', $row['id']) }}"
                       class="btn btn-sm btn-link p-0 text-muted">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-6 d-block mb-2 opacity-25"></i>
                    No transactions for this period.
                    @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
                    <a href="{{ route('transactions.create') }}?customer_id={{ $customer->id }}">Add first entry</a>
                    @endif
                </td>
            </tr>
            @endforelse

            </tbody>

            {{-- Footer: period totals + closing balance ──────────────────── --}}
            <tfoot style="background:#f9fafb;">
                <tr>
                    <td colspan="4" class="text-end fw-bold" style="font-size:12px;">Period Total</td>
                    <td class="text-end fw-bold bal-pos">₹{{ number_format($totalCredit, 2) }}</td>
                    <td class="text-end fw-bold bal-neg">₹{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end"></td>
                    <td></td>
                </tr>
                <tr style="border-top:2px solid #e8ecf0;">
                    <td colspan="4" class="text-end fw-bold" style="font-size:13px;">Closing Balance</td>
                    <td colspan="2"></td>
                    <td class="text-end fw-bold">
                        @php $cb = $closingBalance; @endphp
                        <span class="{{ $cb > 0.01 ? 'bal-neg' : ($cb < -0.01 ? 'bal-pos' : 'bal-zero') }}"
                              style="font-size:14px;">
                            ₹{{ number_format(abs($cb), 2) }}
                        </span>
                        <div style="font-size:10px;"
                             class="{{ $cb > 0.01 ? 'text-danger' : ($cb < -0.01 ? 'text-success' : 'text-muted') }}">
                            {{ $cb > 0.01 ? 'Dr — To Collect' : ($cb < -0.01 ? 'Cr — To Pay' : 'Settled') }}
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

@endsection
