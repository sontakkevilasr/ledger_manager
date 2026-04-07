@extends('layouts.app')
@section('title','Edit — '.$customer->customer_name)
@section('page-title','Edit Customer')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('customers.index') }}" class="btn btn-link text-muted p-0">Customers</a>
    <span class="text-muted">/</span>
    <a href="{{ route('customers.show',$customer) }}" class="btn btn-link text-muted p-0">{{ $customer->customer_name }}</a>
    <span class="text-muted">/</span><span>Edit</span>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-pencil me-2"></i>Edit: {{ $customer->customer_name }}</span>
        <span style="font-size:11px;color:#6c757d;">ID: {{ $customer->id }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('customers.update',$customer) }}">
            @csrf @method('PUT')
            @include('customers._form')
            <hr class="my-4">
            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Update Customer
                </button>
                <a href="{{ route('customers.show',$customer) }}" class="btn btn-outline-secondary">Cancel</a>

                @if(auth()->user()->hasPermission('customers.delete') || auth()->user()->isSuperAdmin())
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto"
                    onclick="if(confirm('Delete this customer? This cannot be undone.')) document.getElementById('delete-customer-form').submit()">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
                @endif
            </div>
        </form>

        @if(auth()->user()->hasPermission('customers.delete') || auth()->user()->isSuperAdmin())
        <form id="delete-customer-form" method="POST" action="{{ route('customers.destroy',$customer) }}"
            onsubmit="return confirm('Delete this customer? This cannot be undone.')">
            @csrf @method('DELETE')
        </form>
        @endif
    </div>
</div>

</div>
</div>
@endsection
