@extends('layouts.core')
@section('title', 'Savings Transactions')

@section('content')
    @include('savings_transactions.partial.header')
    <div class="container-fluid">
        <form method="POST"
              action="{{ isset($transaction) ? route('savings_transactions.update', $transaction->id) : route('savings_transactions.store') }}"
              id="savingsTransactionForm">

            @csrf
            @if(isset($transaction))
                @method('PUT')
            @endif

            <!-- Transaction Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Savings Transaction</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Savings Account <span class="text-danger">*</span></label>
                            <select name="savings_account_id" id="savings_account_id" class="form-select" required>
                                <option value="">Select Savings Account</option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        data-balance="{{ $account->available_balance ?? $account->ledger_balance ?? 0 }}"
                                        {{ old('savings_account_id', $transaction->savings_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_number }}
                                        - {{ $account->member->full_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Transaction No.</label>
                            <input type="text"
                                   name="transaction_number"
                                   class="form-control"
                                   value="{{ old('transaction_number', $transaction->transaction_number ?? $transactionNumber ?? '') }}"
                                   readonly>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Current Balance</label>
                            <input type="text"
                                   id="current_balance"
                                   class="form-control bg-light text-end"
                                   readonly>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                            <select name="transaction_type" id="transaction_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="deposit" {{ old('transaction_type', $transaction->transaction_type ?? '') == 'deposit' ? 'selected' : '' }}>Deposit</option>
                                <option value="withdrawal" {{ old('transaction_type', $transaction->transaction_type ?? '') == 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                                <option value="transfer_in" {{ old('transaction_type', $transaction->transaction_type ?? '') == 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
                                <option value="transfer_out" {{ old('transaction_type', $transaction->transaction_type ?? '') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                                <option value="adjustment" {{ old('transaction_type', $transaction->transaction_type ?? '') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount & Dates -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Amount & Date</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input type="number"
                                       name="amount"
                                       id="amount"
                                       step="0.01"
                                       min="0.01"
                                       class="form-control text-end"
                                       value="{{ old('amount', $transaction->amount ?? '') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Direction</label>
                            <input type="text"
                                   id="direction_display"
                                   class="form-control bg-light"
                                   readonly>

                            <input type="hidden"
                                   name="direction"
                                   id="direction"
                                   value="{{ old('direction', $transaction->direction ?? '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="transaction_date"
                                   class="form-control"
                                   value="{{ old('transaction_date', isset($transaction->transaction_date) ? optional($transaction->transaction_date)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Value Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="value_date"
                                   class="form-control"
                                   value="{{ old('value_date', isset($transaction->value_date) ? optional($transaction->value_date)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">Select Method</option>

                                @foreach([
                                    'cash' => 'Cash',
                                    'mobile_money' => 'Mobile Money',
                                    'bank_transfer' => 'Bank Transfer',
                                    'cheque' => 'Cheque',
                                    'check_off' => 'Check Off',
                                    'internal_transfer' => 'Internal Transfer',
                                    'system' => 'System'
                                ] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('payment_method', $transaction->payment_method ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">External Reference</label>
                            <input type="text"
                                   name="external_reference"
                                   class="form-control"
                                   value="{{ old('external_reference', $transaction->external_reference ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Receipt Number</label>
                            <input type="text"
                                   name="receipt_number"
                                   class="form-control"
                                   value="{{ old('receipt_number', $transaction->receipt_number ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer Details -->
            <div class="card shadow-sm mb-4" id="transferSection">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Transfer Details</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination Savings Account</label>

                            <select name="destination_savings_account_id"
                                    id="destination_savings_account_id"
                                    class="form-select">

                                <option value="">Select Destination Account</option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_number }}
                                        - {{ $account->member->full_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        A transfer should create two savings transactions:
                        <strong>Transfer Out</strong> on the source account and
                        <strong>Transfer In</strong> on the destination account.
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Additional Information</h5>
                </div>

                <div class="card-body">
                    <div class="mb-0">
                        <label class="form-label">Description / Remarks</label>

                        <textarea name="description"
                                  rows="3"
                                  class="form-control">{{ old('description', $transaction->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Transaction Preview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Transaction Preview</h5>
                </div>

                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Current Balance</small>
                            <strong>KES <span id="preview_current">0.00</span></strong>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted d-block">Transaction Amount</small>
                            <strong>KES <span id="preview_amount">0.00</span></strong>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted d-block">Expected Balance</small>
                            <strong>KES <span id="preview_balance">0.00</span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex justify-content-between">
                    <a href="{{ route('savings_transactions.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <div>
                        <button type="submit"
                                name="action"
                                value="pending"
                                class="btn btn-secondary">
                            Save Pending
                        </button>

                        <button type="submit"
                                name="action"
                                value="confirmed"
                                class="btn btn-primary">
                            Confirm Transaction
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
    function updateCurrentBalance() {
        const option = $('#savings_account_id option:selected');
        const balance = parseFloat(option.data('balance')) || 0;

        $('#current_balance').val(balance.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));

        updatePreview();
    }

    function updateDirection() {
        const type = $('#transaction_type').val();
        let direction = '';

        if (['deposit', 'transfer_in'].includes(type)) {
            direction = 'credit';
        }

        if (['withdrawal', 'transfer_out'].includes(type)) {
            direction = 'debit';
        }

        if (type === 'adjustment') {
            direction = $('#direction').val() || 'credit';
        }

        $('#direction').val(direction);
        $('#direction_display').val(
            direction ? direction.charAt(0).toUpperCase() + direction.slice(1) : ''
        );

        $('#transferSection').toggle(
            ['transfer_in', 'transfer_out'].includes(type)
        );

        updatePreview();
    }

    function updatePreview() {
        const option = $('#savings_account_id option:selected');
        const current = parseFloat(option.data('balance')) || 0;
        const amount = parseFloat($('#amount').val()) || 0;
        const direction = $('#direction').val();

        let expected = current;

        if (direction === 'credit') {
            expected = current + amount;
        }

        if (direction === 'debit') {
            expected = current - amount;
        }

        $('#preview_current').text(current.toFixed(2));
        $('#preview_amount').text(amount.toFixed(2));
        $('#preview_balance').text(expected.toFixed(2));
    }

    $('#savings_account_id').on('change', updateCurrentBalance);
    $('#transaction_type').on('change', updateDirection);
    $('#amount').on('input', updatePreview);

    $('#savingsTransactionForm').on('submit', function () {
        const option = $('#savings_account_id option:selected');
        const current = parseFloat(option.data('balance')) || 0;
        const amount = parseFloat($('#amount').val()) || 0;
        const type = $('#transaction_type').val();

        if (amount <= 0) {
            alert('Transaction amount must be greater than zero.');
            return false;
        }

        if (['withdrawal', 'transfer_out'].includes(type) && amount > current) {
            alert('The transaction amount cannot exceed the available savings balance.');
            return false;
        }

        if (['transfer_in', 'transfer_out'].includes(type) && !$('#destination_savings_account_id').val()) {
            alert('Please select the destination savings account.');
            return false;
        }

        if ($('#destination_savings_account_id').val() === $('#savings_account_id').val()) {
            alert('Source and destination savings accounts cannot be the same.');
            return false;
        }

        return true;
    });

    updateCurrentBalance();
    updateDirection();
});
</script>
@endsection