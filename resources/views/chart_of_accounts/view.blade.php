@extends('layouts.core')
@section('title', 'View | Chart Of Accounts')
    

@section('content')
    @include('chart_of_accounts.partial.header')
    @php
        $accountType = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];

        $typeClass = [
            'asset' => 'primary',
            'liability' => 'warning',
            'equity' => 'success',
            'income' => 'info',
            'expense' => 'danger',
        ];
    @endphp

    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-1">{{ $chartOfAccount->name }}</h4>
                    @if($chartOfAccount->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
                <div class="text-muted">
                    Account {{ $chartOfAccount->code }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('chart_of_accounts.edit', $chartOfAccount->id) }}"
                   class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i>
                    Edit
                </a>
            </div>
        </div>


        <div class="row g-4">
            {{-- Account Summary --}}
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            Account Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Account Code
                                </small>
                                <strong class="fs-5">
                                    {{ $chartOfAccount->code }}
                                </strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Account Name
                                </small>
                                <strong>
                                    {{ $chartOfAccount->name }}
                                </strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Account Type
                                </small>
                                <span class="badge bg-{{ $typeClass[$chartOfAccount->account_type] ?? 'secondary' }}">
                                    {{ $accountType[$chartOfAccount->account_type] ?? ucfirst($chartOfAccount->account_type) }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Normal Balance
                                </small>
                                <span class="badge bg-dark">
                                    {{ ucfirst($chartOfAccount->normal_balance) }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Parent Account
                                </small>
                                @if($chartOfAccount->parent)
                                    <a href="{{ route('chart_of_accounts.show', $chartOfAccount->parent->id) }}"
                                       class="text-decoration-none">
                                        {{ $chartOfAccount->parent->code }}
                                        -
                                        {{ $chartOfAccount->parent->name }}
                                    </a>
                                @else
                                    <span class="text-muted">
                                        Root Account
                                    </span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Account Level
                                </small>
                                @php
                                    $level = 1;
                                    $parent = $chartOfAccount->parent;
                                    while ($parent) {
                                        $level++;
                                        $parent = $parent->parent;
                                    }
                                @endphp
                                <span>
                                    Level {{ $level }}
                                </span>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Posting
                                    </small>
                                    @if($chartOfAccount->is_postable)
                                        <span class="badge bg-success">
                                            Postable
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Header
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Control Account
                                    </small>
                                    @if($chartOfAccount->is_control_account)
                                        <span class="badge bg-primary">
                                            Yes
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border">
                                            No
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-2">
                                        Status
                                    </small>
                                    @if($chartOfAccount->is_active)
                                        <span class="badge bg-success">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Account Structure --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-sitemap me-2"></i>
                            Account Structure
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($chartOfAccount->parent)
                            <div class="mb-4">
                                <small class="text-muted d-block mb-2">
                                    Parent
                                </small>
                                <a href="{{ route('chart_of_accounts.show', $chartOfAccount->parent->id) }}"
                                   class="d-flex align-items-center text-decoration-none">
                                    <i class="fas fa-folder text-warning me-2"></i>
                                    <div>
                                        <strong>
                                            {{ $chartOfAccount->parent->code }}
                                        </strong>
                                        <div class="text-muted small">
                                            {{ $chartOfAccount->parent->name }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                        <div>
                            <small class="text-muted d-block mb-2">
                                Current Account
                            </small>
                            <div class="border rounded p-3">
                                <div class="fw-bold">
                                    {{ $chartOfAccount->code }}
                                </div>
                                <div class="text-muted small">
                                    {{ $chartOfAccount->name }}
                                </div>
                            </div>
                        </div>
                        @if($chartOfAccount->children->isNotEmpty())
                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">
                                    Child Accounts
                                </small>
                                @foreach($chartOfAccount->children as $child)
                                    <a href="{{ route('chart_of_accounts.show', $child->id) }}"
                                       class="d-flex align-items-center border-bottom py-2 text-decoration-none">
                                        <i class="fas fa-chevron-right text-muted me-2 small"></i>
                                        <div>
                                            <strong class="small">
                                                {{ $child->code }}
                                            </strong>
                                            <div class="text-muted small">
                                                {{ $child->name }}
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- Description --}}
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-align-left me-2"></i>
                            Description
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($chartOfAccount->description)
                            <p class="mb-0">
                                {!! nl2br(e($chartOfAccount->description)) !!}
                            </p>
                        @else
                            <span class="text-muted">
                                No description provided.
                            </span>
                        @endif
                    </div>
                </div>
            </div>


            {{-- Audit Information --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Audit Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Created
                            </span>
                            <span>
                                {{ optional($chartOfAccount->created_at)->format('d M Y H:i') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">
                                Last Updated
                            </span>
                            <span>
                                {{ optional($chartOfAccount->updated_at)->format('d M Y H:i') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Accounting Rules --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-balance-scale me-2"></i>
                            Accounting Rules
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Normal Balance
                            </span>
                            <strong>
                                {{ ucfirst($chartOfAccount->normal_balance) }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Journal Posting
                            </span>
                            <strong>
                                {{ $chartOfAccount->is_postable ? 'Allowed' : 'Not Allowed' }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">
                                Subledger Control
                            </span>
                            <strong>
                                {{ $chartOfAccount->is_control_account ? 'Yes' : 'No' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Child Accounts --}}
            @if($chartOfAccount->children->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-folder-tree me-2"></i>
                                Child Accounts
                            </h5>
                            <span class="badge bg-light text-dark">
                                {{ $chartOfAccount->children->count() }}
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Normal Balance</th>
                                        <th>Posting</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($chartOfAccount->children as $child)
                                        <tr>
                                            <td>
                                                <strong>
                                                    {{ $child->code }}
                                                </strong>
                                            </td>
                                            <td>
                                                {{ $child->name }}
                                            </td>
                                            <td>
                                                {{ $accountType[$child->account_type] ?? ucfirst($child->account_type) }}
                                            </td>
                                            <td>
                                                {{ ucfirst($child->normal_balance) }}
                                            </td>
                                            <td>
                                                @if($child->is_postable)
                                                    <span class="badge bg-success">
                                                        Postable
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        Header
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($child->is_active)
                                                    <span class="badge bg-success">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>                                            
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection