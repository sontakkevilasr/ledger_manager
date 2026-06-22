<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }

    .header { margin-bottom: 16px; border-bottom: 2px solid #3b5bdb; padding-bottom: 10px; }
    .header h2 { font-size: 16px; color: #3b5bdb; }
    .header p { font-size: 10px; color: #6b7280; margin-top: 3px; }

    .meta { font-size: 10px; color: #6b7280; margin-bottom: 12px; display: flex; justify-content: space-between; }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #3b5bdb; color: #fff; }
    thead th { padding: 6px 8px; text-align: left; font-size: 10px; font-weight: 600; white-space: nowrap; }
    thead th.num { text-align: right; }

    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr { border-bottom: 1px solid #e5e7eb; }
    tbody td { padding: 5px 8px; font-size: 10px; }
    tbody td.num { text-align: right; }

    .credit { color: #059669; font-weight: 600; }
    .debit  { color: #dc2626; font-weight: 600; }
    .badge-credit { background:#d1fae5; color:#065f46; padding:1px 5px; border-radius:3px; font-size:9px; }
    .badge-debit  { background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:3px; font-size:9px; }

    tfoot tr { background: #f1f5f9; border-top: 2px solid #3b5bdb; }
    tfoot td { padding: 6px 8px; font-weight: 700; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
    <h2>Transaction Report</h2>
    <p>
        @if($filters['customer']) Customer: {{ $filters['customer'] }} &nbsp;|&nbsp; @endif
        @if($filters['from'] && $filters['to']) Period: {{ $filters['from'] }} — {{ $filters['to'] }} &nbsp;|&nbsp; @endif
        @if($filters['type']) Type: {{ ucfirst($filters['type']) }} &nbsp;|&nbsp; @endif
        @if($filters['agent']) Agent: {{ $filters['agent'] }} @endif
    </p>
</div>

<div class="meta">
    <span>Total records: {{ $rows->count() }}</span>
    <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
</div>

@php
    $footerColspan = 4 + ($cols['agent'] ? 1 : 0) + ($cols['payment'] ? 1 : 0) + 1; // +1 for Type
@endphp

<table>
    <thead>
        <tr>
            <th style="width:28px;">#</th>
            <th style="width:78px;">Date</th>
            <th>Customer</th>
            <th>Description</th>
            @if($cols['agent'])   <th>Agent</th> @endif
            @if($cols['payment']) <th>Payment</th> @endif
            <th style="width:46px;">Type</th>
            <th class="num" style="width:72px;">Credit</th>
            <th class="num" style="width:72px;">Debit</th>
            @if($cols['by'])      <th>By</th> @endif
        </tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $t)
    <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') }}</td>
        <td>{{ $t->customer?->customer_name ?? '—' }}</td>
        <td>{{ $t->description ?? '—' }}</td>
        @if($cols['agent'])   <td>{{ $t->agent?->name ?? '—' }}</td> @endif
        @if($cols['payment']) <td>{{ $t->paymentType?->payment_type ?? '—' }}</td> @endif
        <td><span class="badge-{{ strtolower($t->type) }}">{{ $t->type }}</span></td>
        <td class="num">@if($t->credit > 0)<span class="credit">{{ number_format($t->credit, 2) }}</span>@endif</td>
        <td class="num">@if($t->debit > 0)<span class="debit">{{ number_format($t->debit, 2) }}</span>@endif</td>
        @if($cols['by'])      <td>{{ $t->createdBy?->name ?? 'Import' }}</td> @endif
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $footerColspan }}" style="text-align:right;">Total</td>
            <td class="num credit">{{ number_format($rows->sum('credit'), 2) }}</td>
            <td class="num debit">{{ number_format($rows->sum('debit'), 2) }}</td>
            @if($cols['by']) <td></td> @endif
        </tr>
    </tfoot>
</table>

</body>
</html>
