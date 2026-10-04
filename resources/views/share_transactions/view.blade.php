@extends('layouts.core')
@section('title', 'View | Share Transactions')

@section('content')
    @include('share_transactions.partial.header')

    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>                
                <h4 class="fw-bold mb-1">{{ $shareTransaction->transaction_number }}</h4>
                <div class="text-muted">
                    {{ optional($shareTransaction->shareAccount)->account_number ?? 'Share Account' }}
                </div>
            </div>
            <div class="d-flex gap-2">                
                @if($shareTransaction->status === 'pending')
                    <a href="{{ route('share_transactions.edit', $shareTransaction->id) }}"
                       class="btn btn-primary">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                    <form method="POST"
                          action="{{ route('share_transactions.confirm', $shareTransaction->id) }}"
                          class="confirm-form">
                        @csrf                        
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check me-1"></i> Confirm
                        </button>
                    </form>
                @endif
                @if($shareTransaction->status === 'confirmed' && !$shareTransaction->reversal)
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#reverseModal">
                        <i class="fa fa-undo me-1"></i> Reverse
                    </button>
                @endif
            </div>
        </div>
        {{-- Status --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Status</div>
                        @if($shareTransaction->status === 'confirmed')
                            <span class="badge bg-success px-3 py-2">
                                <i class="fa fa-check-circle me-1"></i> Confirmed
                            </span>
                        @elseif($shareTransaction->status === 'pending')
                            <span class="badge bg-warning text-dark px-3 py-2">
                                <i class="fa fa-clock me-1"></i> Pending
                            </span>
                        @else
                            <span class="badge bg-danger px-3 py-2">
                                <i class="fa fa-undo me-1"></i> Reversed
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Transaction Type</div>
                        <div class="fw-bold text-capitalize">
                            {{ str_replace('_', ' ', $shareTransaction->transaction_type) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Units</div>
                        <div class="fw-bold fs-5">
                            {{ number_format($shareTransaction->units) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Amount</div>
                        <div class="fw-bold fs-5">
                            KES {{ number_format($shareTransaction->amount, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            {{-- Main --}}
            <div class="col-lg-8">
                {{-- Transaction Details --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-exchange-alt text-primary me-2"></i>
                            Transaction Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Transaction Number</label>
                                <div class="fw-semibold">
                                    {{ $shareTransaction->transaction_number }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Transaction Type</label>
                                <div class="fw-semibold text-capitalize">
                                    {{ str_replace('_', ' ', $shareTransaction->transaction_type) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Direction</label>
                                @if($shareTransaction->direction === 'credit')
                                    <span class="badge bg-success">Credit</span>
                                @else
                                    <span class="badge bg-danger">Debit</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Units</label>
                                <div class="fw-semibold">
                                    {{ number_format($shareTransaction->units) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Unit Value</label>
                                <div class="fw-semibold">
                                    KES {{ number_format($shareTransaction->unit_value, 2) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Transaction Amount</label>
                                <div class="fw-bold">
                                    KES {{ number_format($shareTransaction->amount, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Account Position --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-chart-line text-primary me-2"></i>
                            Account Position After Transaction
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Running Units
                                </label>
                                <div class="fs-4 fw-bold">
                                    {{ number_format($shareTransaction->running_units) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Running Balance
                                </label>
                                <div class="fs-4 fw-bold">
                                    KES {{ number_format($shareTransaction->running_balance, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Dates & Payment --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-calendar-alt text-primary me-2"></i>
                            Dates & Payment
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Transaction Date</label>
                                <div class="fw-semibold">
                                    {{ $shareTransaction->transaction_date }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Value Date</label>
                                <div class="fw-semibold">
                                    {{ $shareTransaction->value_date }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Payment Method</label>
                                <div class="fw-semibold text-capitalize">
                                    {{ $shareTransaction->payment_method
                                        ? str_replace('_', ' ', $shareTransaction->payment_method)
                                        : '—' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Payment Reference</label>
                                <div class="fw-semibold">
                                    {{ $shareTransaction->payment_reference ?: '—' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Receipt Number</label>
                                <div class="fw-semibold">
                                    {{ $shareTransaction->receipt_number ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Description --}}
                @if($shareTransaction->description)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-comment-alt text-primary me-2"></i>
                                Description
                            </h6>
                        </div>
                        <div class="card-body">
                            {{ $shareTransaction->description }}
                        </div>
                    </div>
                @endif
                {{-- Reversal Information --}}
                @if($shareTransaction->reversalOf || $shareTransaction->reversal)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-history text-danger me-2"></i>
                                Reversal Information
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($shareTransaction->reversalOf)
                                <div class="alert alert-warning border-0 mb-0">
                                    This transaction reverses
                                    <a href="{{ route('share_transactions.show', $shareTransaction->reversalOf->id) }}">
                                        {{ $shareTransaction->reversalOf->transaction_number }}
                                    </a>.
                                </div>
                            @endif
                            @if($shareTransaction->reversal)
                                <div class="alert alert-danger border-0 mb-0">
                                    This transaction was reversed by
                                    <a href="{{ route('share_transactions.show', $shareTransaction->reversal->id) }}">
                                        {{ $shareTransaction->reversal->transaction_number }}
                                    </a>.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Share Account --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-wallet text-primary me-2"></i>
                            Share Account
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Account Number</div>
                            <div class="fw-semibold">
                                @if($shareTransaction->shareAccount)
                                    <a href="{{ route('share_accounts.show', $shareTransaction->shareAccount->id) }}">
                                        {{ $shareTransaction->shareAccount->account_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Member</div>
                            <div class="fw-semibold">
                                {{ optional(optional($shareTransaction->shareAccount)->member)->name ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">Share Product</div>
                            <div class="fw-semibold">
                                {{ optional(optional($shareTransaction->shareAccount)->shareProduct)->name ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Audit --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-shield-alt text-primary me-2"></i>
                            Audit Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Recorded By</div>
                            <div class="fw-semibold">
                                {{ optional($shareTransaction->recordedBy)->name ?? 'User #' . $shareTransaction->recorded_by }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Created</div>
                            <div class="fw-semibold">
                                {{ optional($shareTransaction->created_at)->format('d M Y H:i') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">Last Updated</div>
                            <div class="fw-semibold">
                                {{ optional($shareTransaction->updated_at)->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Action --}}
                @if($shareTransaction->status === 'confirmed' && !$shareTransaction->reversal)
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="alert alert-warning border-0 small">
                                <i class="fa fa-info-circle me-1"></i>
                                Confirmed transactions should not be edited.
                                Use the reversal workflow to correct this transaction.
                            </div>
                            <button type="button"
                                    class="btn btn-outline-danger w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reverseModal">
                                <i class="fa fa-undo me-1"></i>
                                Reverse Transaction
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>


    {{-- Reversal Modal --}}
    @if($shareTransaction->status === 'confirmed' && !$shareTransaction->reversal)

    <div class="modal fade"
         id="reverseModal"
         tabindex="-1"
         aria-labelledby="reverseModalLabel"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="reverseModalLabel">
                        Reverse Share Transaction
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('share_transactions.reverse', $shareTransaction->id) }}"
                      id="reverseForm">

                    @csrf
                    <div class="modal-body">

                        <div class="alert alert-danger border-0">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            <strong>This action cannot be undone.</strong>
                            A separate reversal transaction will be created and the
                            original transaction will remain in the ledger.
                        </div>

                        <div class="bg-light rounded p-3 mb-3">

                            <div class="row g-3">

                                <div class="col-6">
                                    <small class="text-muted d-block">Transaction</small>
                                    <strong>{{ $shareTransaction->transaction_number }}</strong>
                                </div>

                                <div class="col-6">
                                    <small class="text-muted d-block">Type</small>
                                    <strong class="text-capitalize">
                                        {{ str_replace('_', ' ', $shareTransaction->transaction_type) }}
                                    </strong>
                                </div>

                                <div class="col-6">
                                    <small class="text-muted d-block">Units</small>
                                    <strong>{{ number_format($shareTransaction->units) }}</strong>
                                </div>

                                <div class="col-6">
                                    <small class="text-muted d-block">Amount</small>
                                    <strong>
                                        KES {{ number_format($shareTransaction->amount, 2) }}
                                    </strong>
                                </div>

                            </div>

                        </div>

                        <div class="mb-3">
                            <label for="reverse_description" class="form-label">
                                Reversal Reason <span class="text-danger">*</span>
                            </label>

                            <textarea name="description"
                                      id="reverse_description"
                                      rows="4"
                                      class="form-control"
                                      placeholder="Enter the reason for reversing this transaction..."
                                      required></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-danger"
                                id="reverseBtn">
                            <i class="fa fa-undo me-1"></i>
                            Reverse Transaction
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

    @endif
@endsection


@section('script')
<script>
$(document).ready(function () {
    $('.confirm-form').on('submit', function (e) {
        if (!confirm('Confirm this share transaction?')) {
            e.preventDefault();
            return false;
        }
    });

    $('#reverseForm').on('submit', function (e) {
        if (!$.trim($('#reverse_description').val())) {
            e.preventDefault();
            alert('Please provide a reason for the reversal.');
            $('#reverse_description').focus();
            return false;
        }

        if (!confirm('Are you sure you want to reverse this transaction?')) {
            e.preventDefault();
            return false;
        }

        $('#reverseBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> Reversing...');
    });
});
</script>
@endsection
