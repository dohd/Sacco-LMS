@extends('layouts.core')
@section('title', 'Loan Disbursements')

@section('content')
    @include('loan_disbursements.partial.header')
    @php
        $statusClasses = [
            'draft' => 'bg-secondary',
            'pending_approval' => 'bg-warning text-dark',
            'approved' => 'bg-primary',
            'processing' => 'bg-info text-dark',
            'processed' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-dark',
            'reversed' => 'bg-danger',
        ];

        $status = $loanDisbursement->status;
        $method = str_replace('_', ' ', $loanDisbursement->disbursement_method);
    @endphp

    <div class="container-fluid py-4">
        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    {{ $loanDisbursement->disbursement_number }}
                </h4>

                <div class="text-muted">
                    Loan Disbursement
                </div>
            </div>

            <div class="d-flex gap-2">
                @if($status === 'draft')
                    <a href="{{ route('loan_disbursements.edit', $loanDisbursement->id) }}"
                       class="btn btn-primary">
                        <i class="fa fa-edit me-1"></i>
                        Edit
                    </a>
                @endif

                @if($status === 'draft')
                    <form method="POST"
                          action="{{ route('loan_disbursements.submit', $loanDisbursement->id) }}"
                          class="submit-form">
                        @csrf

                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-paper-plane me-1"></i>
                            Submit for Approval
                        </button>
                    </form>
                @endif

                @if($status === 'pending_approval')
                    <button type="button"
                            class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#approveModal">
                        <i class="fa fa-check me-1"></i>
                        Approve
                    </button>
                @endif

                @if($status === 'approved')
                    <form method="POST"
                          action="{{ route('loan_disbursements.process', $loanDisbursement->id) }}"
                          class="process-form">
                        @csrf

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-money-bill-wave me-1"></i>
                            Process Disbursement
                        </button>
                    </form>
                @endif
            </div>
        </div>


        {{-- STATUS / AMOUNT SUMMARY --}}
        <div class="row g-3 mb-4">

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="text-muted small mb-2">
                            Status
                        </div>

                        <span class="badge {{ $statusClasses[$status] ?? 'bg-secondary' }} px-3 py-2 text-capitalize">
                            {{ str_replace('_', ' ', $status) }}
                        </span>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Gross Amount
                        </div>

                        <div class="fs-4 fw-bold">
                            KES {{ number_format($loanDisbursement->gross_amount, 2) }}
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Deductions
                        </div>

                        <div class="fs-4 fw-bold text-danger">
                            KES {{ number_format($loanDisbursement->deductions_amount, 2) }}
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Net Amount
                        </div>

                        <div class="fs-4 fw-bold text-success">
                            KES {{ number_format($loanDisbursement->net_amount, 2) }}
                        </div>

                    </div>
                </div>
            </div>

        </div>


        <div class="row g-4">

            {{-- MAIN CONTENT --}}
            <div class="col-xl-8">

                {{-- LOAN INFORMATION --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-file-invoice-dollar text-primary me-2"></i>
                            Loan Information
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Disbursement Number
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->disbursement_number }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Loan Application
                                </label>

                                <div class="fw-semibold">
                                    @if($loanDisbursement->loanApplication)
                                        <a href="{{ route('loan_applications.show', $loanDisbursement->loanApplication->id) }}">
                                            #{{ $loanDisbursement->loanApplication->id }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Loan Account
                                </label>

                                <div class="fw-semibold">
                                    @if($loanDisbursement->loan)
                                        <a href="{{ route('loans.show', $loanDisbursement->loan->id) }}">
                                            {{ $loanDisbursement->loan->account_number ?? '#' . $loanDisbursement->loan->id }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Disbursement Method
                                </label>

                                <div class="fw-semibold text-capitalize">
                                    {{ $method }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                {{-- AMOUNT BREAKDOWN --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-calculator text-primary me-2"></i>
                            Amount Breakdown
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-borderless align-middle mb-0">

                                <tbody>

                                    <tr>
                                        <td class="text-muted">
                                            Gross Loan Amount
                                        </td>

                                        <td class="text-end fw-semibold">
                                            KES {{ number_format($loanDisbursement->gross_amount, 2) }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">
                                            Deductions
                                        </td>

                                        <td class="text-end text-danger">
                                            (KES {{ number_format($loanDisbursement->deductions_amount, 2) }})
                                        </td>
                                    </tr>

                                    <tr class="border-top">

                                        <td class="fw-bold">
                                            Net Amount Released
                                        </td>

                                        <td class="text-end fw-bold text-success fs-5">
                                            KES {{ number_format($loanDisbursement->net_amount, 2) }}
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>
                </div>


                {{-- PAYMENT DETAILS --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-money-check-alt text-primary me-2"></i>
                            Payment Details
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Payment Method
                                </label>

                                <div class="fw-semibold text-capitalize">
                                    {{ $method }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Transaction Reference
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->transaction_reference ?: '—' }}
                                </div>
                            </div>

                            @if($loanDisbursement->disbursement_method === 'bank_transfer')

                                <div class="col-md-6">
                                    <label class="text-muted small d-block">
                                        Bank Name
                                    </label>

                                    <div class="fw-semibold">
                                        {{ $loanDisbursement->bank_name ?: '—' }}
                                    </div>
                                </div>

                            @endif

                            @if($loanDisbursement->disbursement_method === 'cheque')

                                <div class="col-md-6">
                                    <label class="text-muted small d-block">
                                        Cheque Number
                                    </label>

                                    <div class="fw-semibold">
                                        {{ $loanDisbursement->cheque_number ?: '—' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-muted small d-block">
                                        Cheque Date
                                    </label>

                                    <div class="fw-semibold">
                                        {{ $loanDisbursement->cheque_date ?: '—' }}
                                    </div>
                                </div>

                            @endif

                        </div>

                    </div>
                </div>


                {{-- PAYEE DETAILS --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-user text-primary me-2"></i>
                            Payee / Collection Details
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Payee Name
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->payee_name ?: '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    National ID
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->payee_national_id ?: '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Phone Number
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->payee_phone ?: '—' }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                {{-- DATES --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-calendar-alt text-primary me-2"></i>
                            Dates
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-4">
                                <label class="text-muted small d-block">
                                    Disbursement Date
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->disbursement_date ?: '—' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="text-muted small d-block">
                                    Value Date
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->value_date ?: '—' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="text-muted small d-block">
                                    Collection Date
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->collection_date ?: '—' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Postal / Courier Reference
                                </label>

                                <div class="fw-semibold">
                                    {{ $loanDisbursement->postal_reference ?: '—' }}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>


                {{-- SUPPORTING DOCUMENT --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-paperclip text-primary me-2"></i>
                            Supporting Document
                        </h6>
                    </div>

                    <div class="card-body">

                        @if($loanDisbursement->supporting_document)

                            <div class="d-flex align-items-center justify-content-between
                                        border rounded p-3">

                                <div>
                                    <i class="fa fa-file-alt text-primary fs-4 me-2"></i>

                                    <span class="fw-semibold">
                                        Supporting Document
                                    </span>
                                </div>

                                <a href="{{ asset('storage/' . $loanDisbursement->supporting_document) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary">

                                    <i class="fa fa-external-link-alt me-1"></i>
                                    View
                                </a>

                            </div>

                        @else

                            <div class="text-muted">
                                No supporting document attached.
                            </div>

                        @endif

                    </div>
                </div>


                {{-- REMARKS --}}
                @if($loanDisbursement->remarks)

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-comment-alt text-primary me-2"></i>
                                Remarks
                            </h6>
                        </div>

                        <div class="card-body">
                            {!! nl2br(e($loanDisbursement->remarks)) !!}
                        </div>

                    </div>

                @endif


                {{-- REVERSAL --}}
                @if($loanDisbursement->status === 'reversed' ||
                    $loanDisbursement->reversed_at ||
                    $loanDisbursement->reversal_reason)

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-danger text-white py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-undo me-2"></i>
                                Reversal Information
                            </h6>
                        </div>

                        <div class="card-body">

                            <div class="row g-4">

                                <div class="col-md-6">
                                    <label class="text-muted small d-block">
                                        Reversed At
                                    </label>

                                    <div class="fw-semibold">
                                        {{ $loanDisbursement->reversed_at
                                            ? $loanDisbursement->reversed_at->format('d M Y H:i')
                                            : '—' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-muted small d-block">
                                        Reversed By
                                    </label>

                                    <div class="fw-semibold">
                                        {{ optional($loanDisbursement->reversedBy)->name ?? '—' }}
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="text-muted small d-block">
                                        Reversal Reason
                                    </label>

                                    <div>
                                        {!! nl2br(e($loanDisbursement->reversal_reason)) !!}
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                @endif

            </div>


            {{-- SIDEBAR --}}
            <div class="col-xl-4">

                {{-- WORKFLOW --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-route text-primary me-2"></i>
                            Workflow
                        </h6>
                    </div>

                    <div class="card-body">

                        @php
                            $workflow = [
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'processing' => 'Processing',
                                'processed' => 'Processed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'reversed' => 'Reversed',
                            ];
                        @endphp

                        @foreach($workflow as $key => $label)

                            <div class="d-flex align-items-center mb-3">

                                @if($key === $status)
                                    <span class="rounded-circle bg-primary text-white
                                                 d-flex align-items-center justify-content-center"
                                          style="width:32px;height:32px;">
                                        <i class="fa fa-check"></i>
                                    </span>

                                @elseif(
                                    array_search($key, array_keys($workflow)) <
                                    array_search($status, array_keys($workflow))
                                )
                                    <span class="rounded-circle bg-success text-white
                                                 d-flex align-items-center justify-content-center"
                                          style="width:32px;height:32px;">
                                        <i class="fa fa-check"></i>
                                    </span>

                                @else
                                    <span class="rounded-circle bg-light text-muted
                                                 d-flex align-items-center justify-content-center"
                                          style="width:32px;height:32px;">
                                        <i class="fa fa-circle"></i>
                                    </span>
                                @endif

                                <div class="ms-3">
                                    <div class="fw-semibold text-capitalize">
                                        {{ $label }}
                                    </div>

                                    @if($key === $status)
                                        <small class="text-primary">
                                            Current status
                                        </small>
                                    @endif
                                </div>

                            </div>

                        @endforeach

                    </div>
                </div>


                {{-- APPROVAL --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-user-check text-primary me-2"></i>
                            Approval & Processing
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <div class="text-muted small">
                                Approved By
                            </div>

                            <div class="fw-semibold">
                                {{ optional($loanDisbursement->approvedBy)->full_name ?? 'Not yet approved' }}
                            </div>

                            @if($loanDisbursement->approved_at)
                                <small class="text-muted">
                                    {{ dateFormat($loanDisbursement->approved_at, 'd M Y H:i') }}
                                </small>
                            @endif
                        </div>

                        <hr>

                        <div>
                            <div class="text-muted small">
                                Processed By
                            </div>

                            <div class="fw-semibold">
                                {{ optional($loanDisbursement->processedBy)->full_name ?? 'Not yet processed' }}
                            </div>

                            @if($loanDisbursement->processed_at)
                                <small class="text-muted">
                                    {{ dateFormat($loanDisbursement->processed_at, 'd M Y H:i') }}
                                </small>
                            @endif
                        </div>

                    </div>
                </div>


                {{-- AUDIT --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-history text-primary me-2"></i>
                            Record Information
                        </h6>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <div class="text-muted small">
                                Created
                            </div>

                            <div class="fw-semibold">
                                {{ optional($loanDisbursement->created_at)->format('d M Y H:i') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-muted small">
                                Last Updated
                            </div>

                            <div class="fw-semibold">
                                {{ optional($loanDisbursement->updated_at)->format('d M Y H:i') }}
                            </div>
                        </div>

                    </div>
                </div>


                {{-- ACTIONS --}}
                @if($status === 'processed')

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <div class="alert alert-warning border-0 small">
                                <i class="fa fa-info-circle me-1"></i>

                                This disbursement has been processed.
                                It should not be edited. Use the reversal
                                workflow if a correction is required.
                            </div>

                            <button type="button"
                                    class="btn btn-outline-danger w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reverseModal">

                                <i class="fa fa-undo me-1"></i>
                                Reverse Disbursement

                            </button>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- APPROVAL MODAL --}}
    @if($status === 'pending_approval')

    <div class="modal fade"
         id="approveModal"
         tabindex="-1"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Approve Disbursement
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('loan_disbursements.approve', $loanDisbursement->id) }}"
                      id="approveForm">

                    @csrf

                    <div class="modal-body">

                        <div class="alert alert-warning border-0">
                            <i class="fa fa-exclamation-triangle me-1"></i>

                            Confirm that you have reviewed the loan,
                            amount, deductions and payment details before
                            approving this disbursement.
                        </div>

                        <div class="mb-2">
                            <strong>Net Amount:</strong>
                            KES {{ number_format($loanDisbursement->net_amount, 2) }}
                        </div>

                        <div>
                            <strong>Method:</strong>
                            <span class="text-capitalize">
                                {{ $method }}
                            </span>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-success"
                                id="approveBtn">
                            <i class="fa fa-check me-1"></i>
                            Approve Disbursement
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    @endif


    {{-- REVERSAL MODAL --}}
    @if($status === 'processed')

    <div class="modal fade"
         id="reverseModal"
         tabindex="-1"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Reverse Disbursement
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('loan_disbursements.reverse', $loanDisbursement->id) }}"
                      id="reverseForm">

                    @csrf

                    <div class="modal-body">

                        <div class="alert alert-danger border-0">
                            <strong>
                                This is a financial transaction.
                            </strong>

                            The original disbursement will remain in the
                            audit trail and the reversal will be recorded
                            separately.
                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Reversal Reason
                                <span class="text-danger">*</span>
                            </label>

                            <textarea name="reversal_reason"
                                      id="reversal_reason"
                                      rows="4"
                                      class="form-control"
                                      required
                                      placeholder="Enter the reason for reversing this disbursement..."></textarea>

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
                            Reverse Disbursement

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

    $('.submit-form').on('submit', function (e) {
        if (!confirm('Submit this disbursement for approval?')) {
            e.preventDefault();
        }
    });

    $('.process-form').on('submit', function (e) {
        if (!confirm('Start processing this loan disbursement?')) {
            e.preventDefault();
        }
    });

    $('#approveForm').on('submit', function () {

        $('#approveBtn')
            .prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin me-1"></i> Approving...'
            );
    });

    $('#reverseForm').on('submit', function (e) {

        if (!$.trim($('#reversal_reason').val())) {
            e.preventDefault();

            alert('Please provide a reversal reason.');

            $('#reversal_reason').focus();

            return false;
        }

        if (!confirm('Are you sure you want to reverse this disbursement?')) {
            e.preventDefault();

            return false;
        }

        $('#reverseBtn')
            .prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin me-1"></i> Reversing...'
            );
    });

});
</script>
@endsection