@extends('layouts.app')
@section('title','Add Customer')
@section('page-title','Add Customer')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('customers.index') }}" class="btn btn-link text-muted p-0">
        <i class="bi bi-arrow-left me-1"></i>Customers
    </a>
    <span class="text-muted">/</span>
    <span>Add New Customer</span>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-person-plus me-2"></i>New Customer Details</div>
    <div class="card-body">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers._form')
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Customer
                </button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
