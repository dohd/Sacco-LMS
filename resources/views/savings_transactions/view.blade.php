@extends('layouts.core')
@section('title', 'View | Savings Transactions')

@php 
    $tranx = $savingsTransaction;
@endphp

@section('content')
    @include('savings_transactions.partial.header')

    <div class="container-fluid">
        {{-- Page Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <div class="text-muted">
                    Transaction No: <strong>{{ $tranx->transaction_number }}</strong>
                </div>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                @if($tranx->status === 'confirmed' && $tranx->transaction_type !== 'reversal')
                    <button type="button" class="btn btn-danger btn-sm"  id="reverseTransactionBtn">
                        <i class="fa fa-undo"></i>
                        Reverse
                    </button>
                @endif
                @if($tranx->status === 'pending' && $tranx->transaction_type !== 'reversal')
                    {{ Form::open(['route' => ['savings_transactions.confirm', $tranx->id], 'method' => 'POST']) }}
                        <button type="submit" class="btn btn-success btn-sm"  id="confirmTransactionBtn">
                            <i class="fa fa-undo"></i>
                            Confirm
                        </button>
                    {{ Form::close() }}
                @endif                
            </div>
        </div>
        {{-- Transaction Status --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body pt-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-primary fs-6">
                                {{ $tranx->transaction_number }}
                            </span>
                            @if($tranx->status === 'confirmed')
                                <span class="badge bg-success">
                                    Confirmed
                                </span>
                            @elseif($tranx->status === 'pending')
                                <span class="badge bg-warning text-dark">
                                    Pending
                                </span>
                            @elseif($tranx->status === 'reversed')
                                <span class="badge bg-danger">
                                    Reversed
                                </span>
                            @endif
                            <span class="badge {{ $tranx->direction === 'credit' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($tranx->direction) }}
                            </span>
                            <span class="badge bg-info text-dark">
                                {{ ucwords(str_replace('_', ' ', $tranx->transaction_type)) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <small class="text-muted">
                            {{ \Carbon\Carbon::parse($tranx->transaction_date)->format('d M Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        {{-- Financial Summary --}}
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Transaction Amount
                        </div>
                        <h3 class="mb-0 mt-2 {{ $tranx->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $tranx->direction === 'credit' ? '+' : '-' }}
                            KES {{ number_format($tranx->amount, 2) }}
                        </h3>
                        <small class="text-muted">
                            {{ ucfirst($tranx->direction) }} tranx
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Running Balance
                        </div>
                        <h3 class="mb-0 mt-2">
                            KES {{ number_format($tranx->running_balance, 2) }}
                        </h3>
                        <small class="text-muted">
                            Account balance after tranx
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Payment Method
                        </div>
                        <h5 class="mb-0 mt-3">
                            @if($tranx->payment_method)
                                {{ ucwords(str_replace('_', ' ', $tranx->payment_method)) }}
                            @else
                                <span class="text-muted">
                                    Not specified
                                </span>
                            @endif
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            {{-- Transaction Details --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-exchange-alt me-1"></i>
                            Transaction Details
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Transaction Number
                            </div>
                            <div class="col-sm-7 fw-semibold">
                                {{ $tranx->transaction_number }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Transaction Type
                            </div>
                            <div class="col-sm-7">
                                {{ ucwords(str_replace('_', ' ', $tranx->transaction_type)) }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Direction
                            </div>
                            <div class="col-sm-7">
                                @if($tranx->direction === 'credit')
                                    <span class="badge bg-success">
                                        Credit
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        Debit
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Amount
                            </div>
                            <div class="col-sm-7 fw-semibold">
                                KES {{ number_format($tranx->amount, 2) }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Running Balance
                            </div>
                            <div class="col-sm-7 fw-semibold">
                                KES {{ number_format($tranx->running_balance, 2) }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Payment Method
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->payment_method ? ucwords(str_replace('_', ' ', $tranx->payment_method)) : '—' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 text-muted">
                                Status
                            </div>
                            <div class="col-sm-7">
                                @if($tranx->status === 'confirmed')
                                    <span class="badge bg-success">
                                        Confirmed
                                    </span>
                                @elseif($tranx->status === 'pending')
                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        Reversed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Account Information --}}
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-wallet me-1"></i>
                            Savings Account
                        </strong>
                    </div>
                    <div class="card-body">
                        @if($tranx->savingsAccount)
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">
                                    Account Number
                                </div>
                                <div class="col-sm-7 fw-semibold">
                                    {{ $tranx->savingsAccount->account_number }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">
                                    Member
                                </div>
                                <div class="col-sm-7">
                                    @if($tranx->savingsAccount->member)
                                        {{ $tranx->savingsAccount->member->first_name ?? '' }}
                                        {{ $tranx->savingsAccount->member->middle_name ?? '' }}
                                        {{ $tranx->savingsAccount->member->last_name ?? '' }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">
                                    Member Number
                                </div>
                                <div class="col-sm-7">
                                    {{ $tranx->savingsAccount->member->member_number ?? '—' }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">
                                    Product
                                </div>
                                <div class="col-sm-7">
                                    {{ $tranx->savingsAccount->savingsProduct->name ?? '—' }}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-5 text-muted">
                                    Ledger Balance
                                </div>
                                <div class="col-sm-7">
                                    KES {{ number_format($tranx->savingsAccount->ledger_balance, 2) }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-5 text-muted">
                                    Available Balance
                                </div>
                                <div class="col-sm-7 fw-semibold text-success">
                                    KES {{ number_format($tranx->savingsAccount->available_balance, 2) }}
                                </div>
                            </div>
                        @else
                            <div class="text-muted">
                                Savings account information unavailable.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Dates & References --}}
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-calendar me-1"></i>
                            Dates & References
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Transaction Date
                            </div>
                            <div class="col-sm-7">
                                {{ \Carbon\Carbon::parse($tranx->transaction_date)->format('d M Y') }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Value Date
                            </div>
                            <div class="col-sm-7">
                                {{ \Carbon\Carbon::parse($tranx->value_date)->format('d M Y') }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                External Reference
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->external_reference ?? '—' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 text-muted">
                                Receipt Number
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->receipt_number ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Audit Information --}}
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-history me-1"></i>
                            Audit Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Recorded By
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->recordedBy->name ?? '—' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-5 text-muted">
                                Created
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->created_at->format('d M Y H:i:s') }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5 text-muted">
                                Last Updated
                            </div>
                            <div class="col-sm-7">
                                {{ $tranx->updated_at->format('d M Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Description --}}
            @if($tranx->description)
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <strong>
                                <i class="fa fa-comment me-1"></i>
                                Description
                            </strong>
                        </div>
                        <div class="card-body">
                            {!! nl2br(e($tranx->description)) !!}
                        </div>
                    </div>
                </div>
            @endif
            {{-- Reversal Information --}}
            @if($tranx->reversal_of_id || $tranx->status === 'reversed')
                <div class="col-12">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <strong>
                                <i class="fa fa-undo me-1"></i>
                                Reversal Information
                            </strong>
                        </div>
                        <div class="card-body">
                            @if($tranx->reversal_of_id)
                                <div class="row mb-3">
                                    <div class="col-md-3 text-muted">
                                        Reversed Transaction
                                    </div>
                                    <div class="col-md-9">
                                        @if($tranx->reversalOf)
                                            <a href="{{ route('savings_transactions.show', $tranx->reversalOf->id) }}">
                                                {{ $tranx->reversalOf->transaction_number }}
                                            </a>
                                        @else
                                            {{ $tranx->reversal_of_id }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                            @if($tranx->reversalOf)
                                <div class="row">
                                    <div class="col-md-3 text-muted">
                                        Original Transaction Type
                                    </div>
                                    <div class="col-md-9">
                                        {{ ucwords(str_replace('_', ' ', $tranx->reversalOf->transaction_type)) }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Reverse Confirmation --}}
    @if($tranx->status === 'confirmed' && $tranx->transaction_type !== 'reversal')
        <div class="modal fade"
             id="reverseTransactionModal"
             tabindex="-1"
             aria-hidden="true">

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Reverse Transaction
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="alert alert-warning">

                            <strong>Warning:</strong>

                            You are about to reverse transaction
                            <strong>{{ $tranx->transaction_number }}</strong>
                            for

                            <strong>
                                KES {{ number_format($tranx->amount, 2) }}
                            </strong>.

                            This action will create a reversal transaction
                            and retain the original transaction for audit purposes.

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Reversal Reason
                            </label>

                            <textarea id="reversal_reason"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Enter the reason for reversing this transaction"></textarea>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="button"
                                class="btn btn-danger"
                                id="confirmReverseBtn">
                            Confirm Reversal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
<script>
$(document).ready(function () {
    $('#reverseTransactionBtn').on('click', function () {
        $('#reverseTransactionModal').modal('show');
    });

    $('#confirmTransactionBtn').on('click', function (e) {
        if (!confirm('Are you sure to confirm this transaction?')) {
            e.preventDefault();
            return;
        }
    });

    $('#confirmReverseBtn').on('click', function () {
        var reason = $.trim($('#reversal_reason').val());

        if (!reason) {
            $('#reversal_reason').addClass('is-invalid');

            if (!$('#reversal_reason').next('.invalid-feedback').length) {
                $('#reversal_reason').after(
                    '<div class="invalid-feedback">Please provide a reason for the reversal.</div>'
                );
            }

            return;
        }

        $(this)
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        /*
         * Submit reversal request.
         *
         */
        $.ajax({
            url: "{{ route('savings_transactions.reverse', $tranx->id) }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                reversal_reason: reason
            },
            success: function (response) {
                window.location.reload();
            },
            error: function (xhr) {
                $('#confirmReverseBtn')
                    .prop('disabled', false)
                    .html('Confirm Reversal');

                var message = 'Unable to reverse the transaction.';

                if (xhr.responseJSON && xhr.responseJSON.validation_failures) {
                    const { validation_failures } = xhr.responseJSON;
                    if (Array.isArray(validation_failures)) {
                        message = validation_failures.join(' | ');
                    } else if (typeof validation_failures === 'string') {
                         message = validation_failures;  
                    } else if (typeof validation_failures === 'object') {
                        message = validation_failures?.value_date.join(' | ');                         
                    }
                }

                alert(message);
            }
        });
    });
});
</script>
@endsection