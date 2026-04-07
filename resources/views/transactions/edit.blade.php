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
        <form method="POST" action="{{ route('transactions.update', $transaction) }}">
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
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Transaction
                </button>
                <a href="{{ route('customers.show', $transaction->customer_id) }}" class="btn btn-outline-secondary">Cancel</a>

                @if(auth()->user()->hasPermission('transactions.delete') || auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto"
                    onclick="if(confirm('Delete this transaction? This action cannot be undone.')) document.getElementById('delete-txn-form').submit()">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
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
@endsection
