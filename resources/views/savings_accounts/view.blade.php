@extends('layouts.core')
@section('title', 'View | Savings Accounts')
    
@section('content')
    @include('savings_accounts.partial.header')
    <div class="container-fluid">
        {{-- Page Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <div class="text-muted">
                    Account No: <strong>{{ $savingsAccount->account_number }}</strong>
                </div>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                @if($savingsAccount->status === 'active')
                    <button type="button"
                            class="btn btn-success btn-sm"
                            id="depositBtn">
                        <i class="fa fa-plus"></i> Deposit
                    </button>
                    <button type="button"
                            class="btn btn-warning btn-sm"
                            id="withdrawBtn">
                        <i class="fa fa-minus"></i> Withdraw
                    </button>
                @endif
                <a href="{{ route('savings_accounts.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- Account Status --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body pt-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-primary fs-6">
                                {{ $savingsAccount->account_number }}
                            </span>
                            @if($savingsAccount->status === 'active')
                                <span class="badge bg-success">
                                    Active
                                </span>
                            @elseif($savingsAccount->status === 'frozen')
                                <span class="badge bg-warning text-dark">
                                    Frozen
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    Closed
                                </span>
                            @endif
                            @if($savingsAccount->savingsProduct)
                                <span class="badge bg-info text-dark">
                                    {{ $savingsAccount->savingsProduct->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <small class="text-muted">
                            Opened {{ \Carbon\Carbon::parse($savingsAccount->opened_date)->format('d M Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Balance Cards --}}
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Ledger Balance
                        </div>
                        <h3 class="mb-0 mt-2">
                            KES {{ number_format($savingsAccount->ledger_balance, 2) }}
                        </h3>
                        <small class="text-muted">
                            Total posted balance
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Held Balance
                        </div>
                        <h3 class="mb-0 mt-2 text-warning">
                            KES {{ number_format($savingsAccount->held_balance, 2) }}
                        </h3>
                        <small class="text-muted">
                            Funds currently held
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-xl-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Available Balance
                        </div>
                        <h3 class="mb-0 mt-2 text-success">
                            KES {{ number_format($savingsAccount->available_balance, 2) }}
                        </h3>
                        <small class="text-muted">
                            Available for withdrawal
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Member Information --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-user me-1"></i>
                            Member Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Member
                            </div>
                            <div class="col-sm-7 fw-semibold">
                                @if($savingsAccount->member)
                                    {{ $savingsAccount->member->first_name ?? '' }}
                                    {{ $savingsAccount->member->middle_name ?? '' }}
                                    {{ $savingsAccount->member->last_name ?? '' }}
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Member Number
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->member->member_number ?? '—' }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                National ID
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->member->national_id ?? '—' }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Phone
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->member->phone ?? '—' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 text-muted">
                                Email
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->member->email ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Savings Product --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-piggy-bank me-1"></i>
                            Savings Product
                        </strong>
                    </div>
                    <div class="card-body">
                        @if($savingsAccount->savingsProduct)
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Product
                                </div>
                                <div class="col-sm-7 fw-semibold">
                                    {{ $savingsAccount->savingsProduct->name }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Code
                                </div>
                                <div class="col-sm-7">
                                    {{ $savingsAccount->savingsProduct->code }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Type
                                </div>
                                <div class="col-sm-7">
                                    {{ ucwords(str_replace('_', ' ', $savingsAccount->savingsProduct->product_type)) }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Interest Rate
                                </div>
                                <div class="col-sm-7">
                                    {{ number_format($savingsAccount->savingsProduct->interest_rate ?? 0, 2) }}%
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-5 text-muted">
                                    Withdrawals
                                </div>
                                <div class="col-sm-7">
                                    @if($savingsAccount->savingsProduct->allows_withdrawals)
                                        <span class="badge bg-success">Allowed</span>
                                    @else
                                        <span class="badge bg-danger">Not Allowed</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-muted">
                                Savings product information unavailable.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Account Information --}}
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-info-circle me-1"></i>
                            Account Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Account Number
                            </div>
                            <div class="col-sm-7 fw-semibold">
                                {{ $savingsAccount->account_number }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Opened Date
                            </div>
                            <div class="col-sm-7">
                                {{ \Carbon\Carbon::parse($savingsAccount->opened_date)->format('d M Y') }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Opened By
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->openedBy->name ?? '—' }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-5 text-muted">
                                Last Transaction
                            </div>
                            <div class="col-sm-7">
                                @if($savingsAccount->last_transaction_date)
                                    {{ \Carbon\Carbon::parse($savingsAccount->last_transaction_date)->format('d M Y') }}
                                @else
                                    <span class="text-muted">No transactions</span>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 text-muted">
                                Created
                            </div>
                            <div class="col-sm-7">
                                {{ $savingsAccount->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Closure Information --}}
            @if($savingsAccount->status === 'closed')
                <div class="col-lg-6">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <strong>
                                <i class="fa fa-lock me-1"></i>
                                Account Closure
                            </strong>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Closed Date
                                </div>
                                <div class="col-sm-7">
                                    {{ $savingsAccount->closed_date ? \Carbon\Carbon::parse($savingsAccount->closed_date)->format('d M Y') : '—' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5 text-muted">
                                    Closed By
                                </div>
                                <div class="col-sm-7">
                                    {{ $savingsAccount->closedBy->name ?? '—' }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-5 text-muted">
                                    Closure Reason
                                </div>
                                <div class="col-sm-7">
                                    {{ $savingsAccount->closure_reason ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Transactions --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>
                    <i class="fa fa-list me-1"></i>
                    Recent Transactions
                </strong>
                <a href="{{ route('savings_transactions.index', ['account_id' => $savingsAccount->id]) }}"
                   class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if(isset($savingsAccount->transactions) && $savingsAccount->transactions->count())
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction No.</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($savingsAccount->transactions->sortByDesc('transaction_date')->take(10) as $transaction)
                                    <tr>
                                        <td>
                                            {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}
                                        </td>
                                        <td>
                                            <strong>
                                                {{ $transaction->transaction_number }}
                                            </strong>
                                        </td>
                                        <td>
                                            {{ ucwords(str_replace('_', ' ', $transaction->transaction_type)) }}
                                        </td>
                                        <td>
                                            {{ $transaction->external_reference ?? '—' }}
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->direction === 'credit')
                                                <span class="text-success">
                                                    + {{ number_format($transaction->amount, 2) }}
                                                </span>
                                            @else
                                                <span class="text-danger">
                                                    - {{ number_format($transaction->amount, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($transaction->running_balance, 2) }}
                                        </td>
                                        <td>
                                            @if($transaction->status === 'confirmed')
                                                <span class="badge bg-success">
                                                    Confirmed
                                                </span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="badge bg-warning text-dark">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    Reversed
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-receipt fa-2x mb-2"></i>
                        <div>No transactions recorded for this account.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(document).ready(function () {
    $('#depositBtn').on('click', function () {
        window.location.href = "{{ route('savings_transactions.create', ['account_id' => $savingsAccount->id, 'type' => 'deposit']) }}";
    });
    $('#withdrawBtn').on('click', function () {
        window.location.href = "{{ route('savings_transactions.create', ['account_id' => $savingsAccount->id, 'type' => 'withdrawal']) }}";
    });
});
</script>
@endsection