@extends('layouts.core')
@section('title', 'Loan Repayments')

@section('content')
    @include('loan_repayments.partial.header')
        <div class="container-fluid">
        <form method="POST"
              action="{{ isset($repayment) ? route('loan_repayments.update', $repayment->id) : route('loan_repayments.store') }}"
              enctype="multipart/form-data"
              id="loanRepaymentForm">

            @csrf
            @if(isset($repayment))
                @method('PUT')
            @endif

            <!-- Repayment Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Loan Repayment</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Repayment No.</label>
                            <input type="text"
                                   name="repayment_number"
                                   class="form-control"
                                   value="{{ old('repayment_number', $repayment->repayment_number ?? $repaymentNumber ?? '') }}"
                                   readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Member <span class="text-danger">*</span></label>
                            <select name="member_id" id="member_id" class="form-select" required>
                                <option value="">Select Member</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}"
                                        {{ old('member_id', $repayment->member_id ?? '') == $member->id ? 'selected' : '' }}>
                                        {{ $member->membership_number ?? $member->id }} - {{ $member->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">Loan Account <span class="text-danger">*</span></label>
                            <select name="loan_id" id="loan_id" class="form-select" required>
                                <option value="">Select Loan</option>
                                @foreach($loans as $loan)
                                    <option value="{{ $loan->id }}"
                                        data-member-id="{{ $loan->member_id }}"
                                        data-balance="{{ $loan->total_outstanding_balance }}"
                                        {{ old('loan_id', $repayment->loan_id ?? '') == $loan->id ? 'selected' : '' }}>
                                        {{ $loan->loan_number }} - Balance: {{ number_format($loan->total_outstanding_balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="payment_date"
                                   class="form-control"
                                   value="{{ old('payment_date', isset($repayment->payment_date) ? optional($repayment->payment_date)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Value Date</label>
                            <input type="date"
                                   name="value_date"
                                   class="form-control"
                                   value="{{ old('value_date', isset($repayment->value_date) ? optional($repayment->value_date)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Receipt Number</label>
                            <input type="text"
                                   name="receipt_number"
                                   class="form-control"
                                   value="{{ old('receipt_number', $repayment->receipt_number ?? '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Loan Balance</label>
                            <input type="text" id="loan_balance" class="form-control bg-light text-end" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       name="amount_paid"
                                       id="amount_paid"
                                       class="form-control text-end"
                                       value="{{ old('amount_paid', $repayment->amount_paid ?? '') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="">Select Method</option>
                                @foreach([
                                    'cash' => 'Cash',
                                    'bank_transfer' => 'Bank Transfer',
                                    'mobile_money' => 'Mobile Money',
                                    'cheque' => 'Cheque',
                                    'standing_order' => 'Standing Order',
                                    'check_off' => 'Check Off',
                                    'post_dated_cheque' => 'Post Dated Cheque',
                                    'account_credit' => 'Account Credit'
                                ] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('payment_method', $repayment->payment_method ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text"
                                   name="transaction_reference"
                                   class="form-control"
                                   value="{{ old('transaction_reference', $repayment->transaction_reference ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allocation -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Repayment Allocation</h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        Allocation must equal the total amount paid. Any balance not allocated will remain as unallocated funds.
                    </div>

                    <div class="row">
                        @foreach([
                            'principal_amount' => 'Principal',
                            'interest_amount' => 'Interest',
                            'penalty_amount' => 'Penalty',
                            'fees_amount' => 'Fees',
                            'unallocated_amount' => 'Unallocated'
                        ] as $field => $label)
                            <div class="col-md mb-3">
                                <label class="form-label">{{ $label }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">KES</span>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="{{ $field }}"
                                           id="{{ $field }}"
                                           class="form-control text-end allocation-field"
                                           value="{{ old($field, $repayment->$field ?? 0) }}">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="allocationSummary" class="alert alert-secondary mb-0">
                        Allocated: KES <strong id="allocatedTotal">0.00</strong>
                        <span class="mx-2">|</span>
                        Difference: KES <strong id="allocationDifference">0.00</strong>
                    </div>
                </div>
            </div>

            <!-- Payer Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payer Details</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payer Name</label>
                            <input type="text"
                                   name="payer_name"
                                   class="form-control"
                                   value="{{ old('payer_name', $repayment->payer_name ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payer Phone</label>
                            <input type="text"
                                   name="payer_phone"
                                   class="form-control"
                                   value="{{ old('payer_phone', $repayment->payer_phone ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Supporting Document</label>
                            <input type="file"
                                   name="supporting_document"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png">

                            @if(!empty($repayment->supporting_document))
                                <small class="text-muted">
                                    Current file: {{ basename($repayment->supporting_document) }}
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks"
                                  rows="3"
                                  class="form-control">{{ old('remarks', $repayment->remarks ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Workflow -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Transaction Status</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Status</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ ucfirst($repayment->status ?? 'confirmed') }}"
                                   readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Recorded By</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ auth()->user()->name ?? '' }}"
                                   readonly>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('loan_repayments.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <div>
                        <button type="submit" name="action" value="pending" class="btn btn-secondary">
                            Save Pending
                        </button>

                        <button type="submit" name="action" value="confirmed" class="btn btn-primary">
                            Confirm Repayment
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
    function updateLoanBalance() {
        const selected = $('#loan_id option:selected');
        const balance = parseFloat(selected.data('balance')) || 0;
        $('#loan_balance').val(balance.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));

        const memberId = selected.data('member-id');
        if (memberId) $('#member_id').val(memberId);
    }

    function calculateAllocation() {
        const amountPaid = parseFloat($('#amount_paid').val()) || 0;
        let allocated = 0;

        $('.allocation-field').each(function () {
            allocated += parseFloat($(this).val()) || 0;
        });

        const difference = amountPaid - allocated;

        $('#allocatedTotal').text(allocated.toFixed(2));
        $('#allocationDifference').text(difference.toFixed(2));

        const summary = $('#allocationSummary');
        summary.removeClass('alert-success alert-danger alert-secondary');

        if (Math.abs(difference) < 0.01) {
            summary.addClass('alert-success');
        } else {
            summary.addClass('alert-danger');
        }

        return difference;
    }

    function togglePaymentReference() {
        const method = $('#payment_method').val();
        const reference = $('input[name="transaction_reference"]');

        if (['bank_transfer', 'mobile_money', 'cheque', 'standing_order', 'post_dated_cheque'].includes(method)) {
            reference.prop('required', true);
        } else {
            reference.prop('required', false);
        }
    }

    $('#loan_id').on('change', updateLoanBalance);
    $('#amount_paid, .allocation-field').on('input', calculateAllocation);
    $('#payment_method').on('change', togglePaymentReference);

    updateLoanBalance();
    calculateAllocation();
    togglePaymentReference();

    $('#loanRepaymentForm').on('submit', function () {
        const difference = calculateAllocation();

        if (Math.abs(difference) >= 0.01) {
            alert('The repayment allocation must equal the total amount paid.');
            return false;
        }

        const amountPaid = parseFloat($('#amount_paid').val()) || 0;
        if (amountPaid <= 0) {
            alert('Amount paid must be greater than zero.');
            return false;
        }

        return true;
    });
});
</script>
@endsection
