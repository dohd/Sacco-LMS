@extends('layouts.core')
@section('title', 'Savings Withdrawals')

@section('content')
    @include('savings_withdrawals.partial.header')
    <div class="container-fluid">
        <form method="POST"
              action="{{ isset($withdrawalRequest)
                    ? route('savings_withdrawals.update', $withdrawalRequest->id)
                    : route('savings_withdrawals.store') }}"
              id="withdrawalRequestForm">

            @csrf

            @isset($withdrawalRequest)
                @method('PUT')
            @endisset

            <!-- ========================================= -->
            <!-- REQUEST DETAILS -->
            <!-- ========================================= -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-primary text-white">

                    <h5 class="mb-0">
                        Savings Withdrawal Request
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3 mb-3">

                            <label class="form-label">
                                Request No.
                            </label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ old('request_number', $withdrawalRequest->request_number ?? $requestNumber ?? '') }}"
                                   readonly>

                        </div>

                        <div class="col-md-5 mb-3">

                            <label class="form-label">
                                Savings Account
                                <span class="text-danger">*</span>
                            </label>

                            <select name="savings_account_id"
                                    id="savings_account_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Savings Account
                                </option>

                                @foreach($accounts as $account)

                                    <option value="{{ $account->id }}"
                                        data-balance="{{ $account->available_balance }}"
                                        {{ old('savings_account_id', $withdrawalRequest->savings_account_id ?? '') == $account->id ? 'selected' : '' }}>

                                        {{ $account->account_number }}
                                        -
                                        {{ $account->member->full_name }}

                                    </option>

                                @endforeach

                            </select>

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
                                       id="available_balance"
                                       class="form-control text-end bg-light"
                                       readonly>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ========================================= -->
            <!-- WITHDRAWAL DETAILS -->
            <!-- ========================================= -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-success text-white">

                    <h5 class="mb-0">
                        Withdrawal Details
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Amount
                                <span class="text-danger">*</span>
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    KES
                                </span>

                                <input type="number"
                                       name="amount"
                                       id="amount"
                                       step="0.01"
                                       min="0.01"
                                       class="form-control text-end"
                                       value="{{ old('amount', $withdrawalRequest->amount ?? '') }}"
                                       required>

                            </div>

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Requested Date
                                <span class="text-danger">*</span>
                            </label>

                            <input type="date"
                                   name="requested_date"
                                   class="form-control"
                                   value="{{ old('requested_date', isset($withdrawalRequest->requested_date)
                                        ? optional($withdrawalRequest->requested_date)->format('Y-m-d')
                                        : now()->format('Y-m-d')) }}"
                                   required>

                        </div>

                        <div class="col-md-4 mb-3">

                            <label class="form-label">
                                Payment Method
                                <span class="text-danger">*</span>
                            </label>

                            <select name="payment_method"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Method
                                </option>

                                <option value="cash"
                                    {{ old('payment_method', $withdrawalRequest->payment_method ?? '') == 'cash' ? 'selected' : '' }}>
                                    Cash
                                </option>

                                <option value="mobile_money"
                                    {{ old('payment_method', $withdrawalRequest->payment_method ?? '') == 'mobile_money' ? 'selected' : '' }}>
                                    Mobile Money
                                </option>

                                <option value="bank_transfer"
                                    {{ old('payment_method', $withdrawalRequest->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>
                                    Bank Transfer
                                </option>

                                <option value="cheque"
                                    {{ old('payment_method', $withdrawalRequest->payment_method ?? '') == 'cheque' ? 'selected' : '' }}>
                                    Cheque
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="mb-0">

                        <label class="form-label">
                            Reason for Withdrawal
                        </label>

                        <textarea name="reason"
                                  rows="4"
                                  class="form-control">{{ old('reason', $withdrawalRequest->reason ?? '') }}</textarea>

                    </div>

                </div>

            </div>

            <!-- ========================================= -->
            <!-- REQUEST STATUS -->
            <!-- ========================================= -->

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-warning">

                    <h5 class="mb-0">
                        Request Status
                    </h5>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">

                            <label class="form-label">
                                Current Status
                            </label>

                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ ucfirst($withdrawalRequest->status ?? 'Pending') }}"
                                   readonly>

                        </div>

                        @isset($withdrawalRequest)

                            @if($withdrawalRequest->approved_at)

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Approved At
                                    </label>

                                    <input type="text"
                                           class="form-control bg-light"
                                           value="{{ $withdrawalRequest->approved_at }}"
                                           readonly>

                                </div>

                            @endif

                        @endisset

                    </div>

                </div>

            </div>

            <!-- ========================================= -->
            <!-- ACTIONS -->
            <!-- ========================================= -->

            <div class="card shadow-sm">

                <div class="card-body d-flex justify-content-between">

                    <a href="{{ route('savings_withdrawals.index') }}"
                       class="btn btn-light">

                        Cancel

                    </a>

                    <div>

                        <button type="submit"
                                name="action"
                                value="draft"
                                class="btn btn-secondary">

                            Save

                        </button>

                        <button type="submit"
                                name="action"
                                value="submit"
                                class="btn btn-primary">

                            Submit Request

                        </button>

                    </div>

                </div>

            </div>

        </form>
    </div>
@stop

@section('script')
<script>
$(function () {
    function updateBalance() {

        let balance = parseFloat(
            $('#savings_account_id option:selected').data('balance')
        ) || 0;

        $('#available_balance').val(
            balance.toLocaleString(undefined,{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            })
        );

    }

    $('#savings_account_id').change(function () {

        updateBalance();

    });

    $('#withdrawalRequestForm').submit(function () {

        let balance = parseFloat(
            $('#savings_account_id option:selected').data('balance')
        ) || 0;

        let amount = parseFloat(
            $('#amount').val()
        ) || 0;

        if (amount <= 0) {

            alert('Withdrawal amount must be greater than zero.');

            return false;

        }

        if (amount > balance) {

            alert('Withdrawal amount exceeds the available balance.');

            return false;

        }

        return true;

    });

    updateBalance();
});
</script>
@endsection