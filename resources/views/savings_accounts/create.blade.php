@extends('layouts.core')
@section('title', 'Savings Accounts')

@section('content')
    @include('savings_accounts.partial.header')
    <div class="container-fluid">
        <form method="POST"
              action="{{ isset($account)
                ? route('savings_accounts.update', $account->id)
                : route('savings_accounts.store') }}"
              id="savingsAccountForm">

            @csrf

            @isset($account)
                @method('PUT')
            @endisset

            <!-- ============================= -->
            <!-- ACCOUNT DETAILS -->
            <!-- ============================= -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">
                        Savings Account
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-5 mb-3">

                            <label class="form-label">
                                Member
                                <span class="text-danger">*</span>
                            </label>

                            <select name="member_id"
                                    id="member_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Member
                                </option>

                                @foreach($members as $member)

                                    <option value="{{ $member->id }}"
                                        {{ old('member_id', $account->member_id ?? '') == $member->id ? 'selected' : '' }}>

                                        {{ $member->member_number }}
                                        -
                                        {{ $member->full_name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Savings Product
                                <span class="text-danger">*</span>
                            </label>

                            <select name="savings_product_id"
                                    id="savings_product_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Product
                                </option>

                                @foreach($products as $product)

                                    <option value="{{ $product->id }}"
                                        {{ old('savings_product_id', $account->savings_product_id ?? '') == $product->id ? 'selected' : '' }}>

                                        {{ $product->code }}
                                        -
                                        {{ $product->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-md-3 mb-3">

                            <label class="form-label">
                                Account Number
                            </label>

                            <input type="text"
                                   name="account_number"
                                   class="form-control"
                                   value="{{ old('account_number', $account->account_number ?? $accountNumber ?? '') }}"
                                   readonly>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-3 mb-3">

                            <label class="form-label">
                                Opened Date
                                <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="opened_date"
                                   class="form-control"
                                   value="{{ old('opened_date', isset($account->opened_date)
                                        ? optional($account->opened_date)->format('Y-m-d')
                                        : now()->format('Y-m-d')) }}"
                                   required>

                        </div>

                        <div class="col-md-3 mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                    class="form-select">

                                <option value="active"
                                    {{ old('status', $account->status ?? 'active') == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="frozen"
                                    {{ old('status', $account->status ?? '') == 'frozen' ? 'selected' : '' }}>
                                    Frozen
                                </option>

                                <option value="closed"
                                    {{ old('status', $account->status ?? '') == 'closed' ? 'selected' : '' }}>
                                    Closed
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================= -->
            <!-- ACCOUNT BALANCES -->
            <!-- ============================= -->

            @isset($account)

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-success text-white">

                    <h5 class="mb-0">
                        Account Balances
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Ledger Balance
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    KES
                                </span>

                                <input type="text"
                                       class="form-control text-end bg-light"
                                       value="{{ number_format($account->ledger_balance,2) }}"
                                       readonly>

                            </div>

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Held Balance
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    KES
                                </span>

                                <input type="text"
                                       class="form-control text-end bg-light"
                                       value="{{ number_format($account->held_balance,2) }}"
                                       readonly>

                            </div>

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Available Balance
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    KES
                                </span>

                                <input type="text"
                                       class="form-control text-end bg-light"
                                       value="{{ number_format($account->available_balance,2) }}"
                                       readonly>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            @endisset

            <!-- ============================= -->
            <!-- ACTIONS -->
            <!-- ============================= -->

            <div class="card shadow-sm">

                <div class="card-body d-flex justify-content-between">

                    <a href="{{ route('savings_accounts.index') }}"
                       class="btn btn-light">

                        Cancel

                    </a>

                    <button type="submit"
                            class="btn btn-primary">

                        Save Savings Account

                    </button>

                </div>

            </div>

        </form>
    </div>    
@stop

@section('script')
<script>
$(function () {

    $('#member_id').change(function () {

        // Optional:
        // Load member details,
        // existing savings accounts,
        // eligibility,
        // etc.

    });

    $('#savings_product_id').change(function () {

        // Optional:
        // Show product rules,
        // minimum balance,
        // contribution requirements,
        // withdrawal rules,
        // etc.

    });

});
</script>
@endsection