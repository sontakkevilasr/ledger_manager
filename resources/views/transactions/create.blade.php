@extends('layouts.app')
@section('title','Add Entry')
@section('page-title','Add Transaction Entry')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

<div class="card">
    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>New Transaction Entry</div>
    <div class="card-body">
        <form method="POST" action="{{ route('transactions.store') }}" id="txn-form">
            @csrf

            {{-- Type selector --}}
            <div class="mb-4">
                <label class="form-label fw-500">Entry Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="type" id="type-credit" value="Credit"
                        {{ old('type', request('type','Credit')) === 'Credit' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success w-50" for="type-credit">
                        <i class="bi bi-arrow-down-circle me-1"></i>Credit (Payment Received)
                    </label>

                    <input type="radio" class="btn-check" name="type" id="type-debit" value="Debit"
                        {{ old('type', request('type')) === 'Debit' ? 'checked' : '' }}>
                    <label class="btn btn-outline-danger w-50" for="type-debit">
                        <i class="bi bi-arrow-up-circle me-1"></i>Debit (Bill / Goods Sent)
                    </label>
                </div>
                @error('type')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
                {{-- Customer --}}
                <div class="col-12">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer-select"
                        class="form-select @error('customer_id') is-invalid @enderror" required>
                        <option value="">— Select Customer —</option>
                        @foreach($customers as $id => $name)
                        <option value="{{ $id }}"
                            {{ old('customer_id', $selectedCustomer?->id ?? request('customer_id')) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    {{-- Live balance display --}}
                    <div id="balance-display" class="mt-2 p-2 rounded" style="background:#f4f6fb;display:none;font-size:13px;">
                        Current balance: <strong id="current-balance"></strong>
                    </div>
                </div>

                {{-- Amount --}}
                <div class="col-md-6">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" step="0.01" min="0.01"
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount') }}" placeholder="0.00" required>
                    </div>
                    @error('amount')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                </div>

                {{-- Date --}}
                <div class="col-md-6">
                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date"
                        class="form-control @error('transaction_date') is-invalid @enderror"
                        value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                    @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control"
                        value="{{ old('description') }}"
                        placeholder="e.g. BDL 5 HUBLI or AC ME HASINUDDIN">
                </div>

                {{-- Payment Type --}}
                <div class="col-md-6">
                    <label class="form-label">Payment Type</label>
                    <select name="payment_type_id" class="form-select">
                        <option value="">— Select —</option>
                        @foreach($paymentTypes as $id => $type)
                        <option value="{{ $id }}" {{ old('payment_type_id') == $id ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Agent --}}
                <div class="col-md-6">
                    <label class="form-label">Agent / Remark</label>
                    <select name="agent_id" class="form-select">
                        <option value="">— Select —</option>
                        @foreach($agents as $id => $name)
                        <option value="{{ $id }}" {{ old('agent_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Remark (free text) --}}
                <div class="col-12">
                    <label class="form-label">Additional Remark</label>
                    <input type="text" name="remark" class="form-control" value="{{ old('remark') }}" placeholder="Optional note">
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <i class="bi bi-check-lg me-1"></i>Save Entry
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetAndAddAnother()">
                    Save & Add Another
                </button>
                <a href="{{ route('transactions.index') }}" class="btn btn-link text-muted">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection

@push('scripts')
<script>
// Update button color based on type selection
document.querySelectorAll('input[name="type"]').forEach(r => {
    r.addEventListener('change', () => {
        const btn = document.getElementById('submit-btn');
        btn.className = r.value === 'Credit'
            ? 'btn btn-success'
            : 'btn btn-danger';
        btn.innerHTML = r.value === 'Credit'
            ? '<i class="bi bi-check-lg me-1"></i>Save Credit Entry'
            : '<i class="bi bi-check-lg me-1"></i>Save Debit Entry';
    });
});

// Fetch live balance when customer selected
document.getElementById('customer-select').addEventListener('change', function() {
    const id = this.value;
    if (!id) { document.getElementById('balance-display').style.display='none'; return; }
    fetch(`/api/customers/${id}/balance`)
        .then(r => r.json())
        .then(d => {
            const bal = d.balance;
            const el  = document.getElementById('balance-display');
            const balEl = document.getElementById('current-balance');
            el.style.display = 'block';
            const divisor = {{ scale_divisor() }};
            const scaled  = Math.abs(bal) / divisor;
            const fmt = '₹' + scaled.toLocaleString('en-IN', {minimumFractionDigits:2});
            balEl.textContent = fmt + (bal > 0 ? ' (to collect)' : bal < 0 ? ' (to pay)' : ' (settled)');
            balEl.style.color = bal > 0 ? '#059669' : bal < 0 ? '#dc2626' : '#6b7280';
        });
});

// Trigger on page load if customer pre-selected
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('customer-select');
    if (sel.value) sel.dispatchEvent(new Event('change'));
});

function resetAndAddAnother() {
    document.getElementById('txn-form').setAttribute('data-add-another','1');
    document.getElementById('txn-form').submit();
}
</script>
@endpush
