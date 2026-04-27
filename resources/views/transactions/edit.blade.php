@extends('layouts.app')
@section('title','Edit Transaction')
@section('page-title','Edit Transaction')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('customers.show', $transaction->customer_id) }}" class="btn btn-link text-muted p-0">
        <i class="bi bi-arrow-left me-1"></i>Back to Ledger
    </a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pencil me-2"></i>Edit Transaction #{{ $transaction->id }}</span>
        <span class="badge {{ $transaction->type === 'Credit' ? 'badge-credit' : 'badge-debit' }}">
            {{ $transaction->type }}
        </span>
    </div>
    <div class="card-body">
        <form id="edit-txn-form" method="POST" action="{{ route('transactions.update', $transaction) }}">
            @csrf @method('PUT')

            {{-- Type --}}
            <div class="mb-4">
                <label class="form-label fw-500">Entry Type</label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="type" id="type-credit" value="Credit"
                        {{ $transaction->type === 'Credit' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success w-50" for="type-credit">
                        <i class="bi bi-arrow-down-circle me-1"></i>Credit
                    </label>
                    <input type="radio" class="btn-check" name="type" id="type-debit" value="Debit"
                        {{ $transaction->type === 'Debit' ? 'checked' : '' }}>
                    <label class="btn btn-outline-danger w-50" for="type-debit">
                        <i class="bi bi-arrow-up-circle me-1"></i>Debit
                    </label>
                </div>
            </div>

            <div class="row g-3">
                {{-- Customer --}}
                <div class="col-12">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select" required>
                        @foreach($customers as $id => $name)
                        <option value="{{ $id }}" {{ $transaction->customer_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Amount --}}
                <div class="col-md-6">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" step="0.01" min="0.01"
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', $transaction->credit > 0 ? $transaction->credit : $transaction->debit) }}"
                            required>
                    </div>
                </div>

                {{-- Date --}}
                <div class="col-md-6">
                    <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                    <input type="date" name="transaction_date" class="form-control"
                        value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control"
                        value="{{ old('description', $transaction->description) }}">
                </div>

                {{-- Payment Type --}}
                <div class="col-md-6">
                    <label class="form-label">Payment Type</label>
                    <select name="payment_type_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($paymentTypes as $id => $type)
                        <option value="{{ $id }}" {{ $transaction->payment_type_id == $id ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Agent --}}
                <div class="col-md-6">
                    <label class="form-label">Agent</label>
                    <select name="agent_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($agents as $id => $name)
                        <option value="{{ $id }}" {{ $transaction->agent_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Remark --}}
                <div class="col-12">
                    <label class="form-label">Remark</label>
                    <input type="text" name="remark" class="form-control"
                        value="{{ old('remark', $transaction->remark) }}">
                </div>
            </div>

            <hr class="my-4">

            {{-- Audit info --}}
            <div class="mb-3" style="font-size:12px;color:#6c757d;">
                Created by <strong>{{ $transaction->createdBy?->name ?? 'Import' }}</strong>
                on {{ $transaction->created_at->format('d M Y, h:i A') }}
            </div>

            <div class="d-flex gap-2">
                @if(config('app.allow_transaction_edit'))
                <button type="button" class="btn btn-primary" onclick="openConfirmModal('update')">
                    <i class="bi bi-check-lg me-1"></i>Update Transaction
                </button>
                @else
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Transaction
                </button>
                @endif
                <a href="{{ route('customers.show', $transaction->customer_id) }}" class="btn btn-outline-secondary">Cancel</a>

                @if(auth()->user()->hasPermission('transactions.delete') || auth()->user()->isSuperAdmin())
                @if(config('app.allow_transaction_edit'))
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto" onclick="openConfirmModal('delete')">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
                @else
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto"
                    onclick="if(confirm('Delete this transaction? This cannot be undone.')) document.getElementById('delete-txn-form').submit()">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
                @endif
                @endif
            </div>
        </form>

        @if(auth()->user()->hasPermission('transactions.delete') || auth()->user()->isSuperAdmin())
        <form id="delete-txn-form" method="POST" action="{{ route('transactions.destroy', $transaction) }}">
            @csrf @method('DELETE')
        </form>
        @endif
    </div>
</div>

</div>
</div>

{{-- ── Confirmation Modal (only when allow_transaction_edit is ON) ─────── --}}
@if(config('app.allow_transaction_edit'))
<div id="confirm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;max-width:440px;width:calc(100% - 32px);box-shadow:0 20px 50px rgba(0,0,0,.25);">

        <div class="d-flex align-items-center gap-3 mb-3">
            <div id="modal-icon" style="width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i id="modal-icon-i" style="font-size:20px;"></i>
            </div>
            <div>
                <div id="modal-title" style="font-size:15px;font-weight:700;color:#111827;"></div>
                <div id="modal-subtitle" style="font-size:12px;color:#6b7280;margin-top:2px;"></div>
            </div>
        </div>

        <div class="mb-4">
            <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">
                Type <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;color:#dc2626;">{{ $customerName }}</code> to confirm:
            </label>
            <input type="text" id="confirm-input"
                class="form-control"
                placeholder="Type company name exactly"
                autocomplete="off"
                oninput="checkConfirmName(this.value)"
                style="font-size:14px;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Case-sensitive. Must match exactly.</div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" id="confirm-submit-btn" class="btn flex-grow-1" disabled
                style="font-size:14px;font-weight:600;opacity:0.5;" onclick="submitConfirmed()">
            </button>
            <button type="button" class="btn btn-outline-secondary px-4" onclick="closeConfirmModal()">Cancel</button>
        </div>
    </div>
</div>

@endif

@endsection

@push('scripts')
@if(config('app.allow_transaction_edit'))
<script>
const COMPANY_NAME = @json($customerName);
let pendingAction = null;

function openConfirmModal(action) {
    pendingAction = action;
    const isDelete = action === 'delete';

    document.getElementById('modal-icon').style.background  = isDelete ? '#fef2f2' : '#eff3ff';
    document.getElementById('modal-icon-i').className       = isDelete ? 'bi bi-trash text-danger' : 'bi bi-pencil text-primary';
    document.getElementById('modal-title').textContent      = isDelete ? 'Confirm Delete' : 'Confirm Update';
    document.getElementById('modal-subtitle').textContent   = isDelete
        ? 'This will permanently remove the transaction.'
        : 'This will save changes to the transaction.';

    const btn = document.getElementById('confirm-submit-btn');
    btn.textContent  = isDelete ? 'Yes, Delete' : 'Yes, Update';
    btn.className    = 'btn flex-grow-1 ' + (isDelete ? 'btn-danger' : 'btn-primary');

    document.getElementById('confirm-input').value = '';
    btn.disabled    = true;
    btn.style.opacity = '0.5';

    const modal = document.getElementById('confirm-modal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('confirm-input').focus(), 100);
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').style.display = 'none';
    pendingAction = null;
}

function checkConfirmName(val) {
    const btn = document.getElementById('confirm-submit-btn');
    const ok  = val === COMPANY_NAME;
    btn.disabled      = !ok;
    btn.style.opacity = ok ? '1' : '0.5';
}

function submitConfirmed() {
    if (pendingAction === 'delete') {
        document.getElementById('delete-txn-form').submit();
    } else {
        document.getElementById('edit-txn-form').submit();
    }
}

document.getElementById('confirm-modal').addEventListener('click', function(e) {
    if (e.target === this) closeConfirmModal();
});
</script>
@endif
@endpush
