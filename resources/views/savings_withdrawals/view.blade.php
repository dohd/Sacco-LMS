@extends('layouts.core')
@section('title', 'View | Savings Withdrawal Requests')
    
@section('content')
    @include('savings_withdrawals.partial.header')
    @php
        $withdrawal = $savingsWithdrawal;
    @endphp
    <div class="container-fluid">
        {{-- ================================================================ --}}
        {{-- HEADER --}}
        {{-- ================================================================ --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>                
                <div class="text-muted">
                    Request #{{ $withdrawal->request_number }}
                </div>
            </div>
            <div class="d-flex gap-2">                
                @if($withdrawal->status === 'pending')
                    <a href="{{ route('savings_withdrawals.edit', $withdrawal->id) }}"
                       class="btn btn-primary btn-sm">
                        <i class="fa fa-edit me-1"></i>
                        Edit
                    </a>
                @endif
            </div>
        </div>
        {{-- ================================================================ --}}
        {{-- STATUS --}}
        {{-- ================================================================ --}}
        @php
            $statusClass = [
                'pending' => 'bg-warning text-dark',
                'approved' => 'bg-info text-dark',
                'rejected' => 'bg-danger',
                'paid' => 'bg-success',
                'cancelled' => 'bg-secondary',
            ][$withdrawal->status] ?? 'bg-secondary';
        @endphp
        <div class="alert d-flex flex-wrap justify-content-between align-items-center {{ $statusClass }}">
            <div>
                <strong>
                    Status:
                    {{ ucwords(str_replace('_', ' ', $withdrawal->status)) }}
                </strong>
                @if($withdrawal->status === 'pending')
                    <div class="small">
                        This withdrawal is awaiting approval.
                    </div>
                @elseif($withdrawal->status === 'approved')
                    <div class="small">
                        Withdrawal approved and awaiting payment.
                    </div>
                @elseif($withdrawal->status === 'paid')
                    <div class="small">
                        Withdrawal has been paid.
                    </div>
                @elseif($withdrawal->status === 'rejected')
                    <div class="small">
                        Withdrawal request was rejected.
                    </div>
                @endif
            </div>
            {{-- ACTIONS --}}
            <div class="mt-2 mt-md-0">
                @if($withdrawal->status === 'pending')
                    <button type="button"
                            class="btn btn-success btn-sm"
                            id="approveBtn">
                        <i class="fa fa-check me-1"></i>
                        Approve
                    </button>
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            id="rejectBtn">
                        <i class="fa fa-times me-1"></i>
                        Reject
                    </button>
                @elseif($withdrawal->status === 'approved')
                    <button type="button"
                            class="btn btn-success btn-sm"
                            id="payBtn">
                        <i class="fa fa-money-bill-wave me-1"></i>
                        Pay Withdrawal
                    </button>
                @endif
            </div>
        </div>
        <div class="row g-3">
            {{-- ============================================================ --}}
            {{-- WITHDRAWAL INFORMATION --}}
            {{-- ============================================================ --}}
            <div class="col-lg-8">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-file-invoice me-1"></i>
                            Withdrawal Details
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Request Number
                                </div>
                                <div class="fw-semibold">
                                    {{ $withdrawal->request_number }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Requested Date
                                </div>
                                <div class="fw-semibold">
                                    {{ optional($withdrawal->requested_date)->format('d M Y') ?? $withdrawal->requested_date }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Amount
                                </div>
                                <div class="fw-bold fs-5">
                                    KES {{ number_format($withdrawal->amount, 2) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Payment Method
                                </div>
                                <div class="fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $withdrawal->payment_method)) }}
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="text-muted small">
                                    Reason
                                </div>
                                <div>
                                    {{ $withdrawal->reason ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- SAVINGS ACCOUNT --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-wallet me-1"></i>
                            Savings Account
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Account Number
                                </div>
                                <div class="fw-semibold">
                                    @if($withdrawal->savingsAccount)
                                        <a href="{{ route('savings_accounts.show', $withdrawal->savingsAccount->id) }}">
                                            {{ $withdrawal->savingsAccount->account_number }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Member
                                </div>
                                <div class="fw-semibold">
                                    @if($withdrawal->savingsAccount && $withdrawal->savingsAccount->member)
                                        {{ $withdrawal->savingsAccount->member->member_number ?? '' }}
                                        -
                                        {{ $withdrawal->savingsAccount->member->first_name ?? '' }}
                                        {{ $withdrawal->savingsAccount->member->last_name ?? '' }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Savings Product
                                </div>
                                <div class="fw-semibold">
                                    {{ $withdrawal->savingsAccount->savingsProduct->name ?? '—' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Ledger Balance
                                </div>
                                <div>
                                    KES
                                    {{ number_format($withdrawal->savingsAccount->ledger_balance ?? 0, 2) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Held Balance
                                </div>
                                <div>
                                    KES
                                    {{ number_format($withdrawal->savingsAccount->held_balance ?? 0, 2) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">
                                    Available Balance
                                </div>
                                <div class="fw-bold">
                                    KES
                                    {{ number_format($withdrawal->savingsAccount->available_balance ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- PAYMENT INFORMATION --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-credit-card me-1"></i>
                            Payment Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($withdrawal->payment_method === 'mobile_money')
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Mobile Money Number
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->mobile_money_number ?: '—' }}
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->payment_method === 'bank_transfer')
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Bank
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->bank_name ?: '—' }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Bank Account
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->bank_account_number ?: '—' }}
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->payment_method === 'cheque')
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Cheque Number
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->cheque_number ?: '—' }}
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->payment_method === 'cash')
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Payment Method
                                    </div>
                                    <div class="fw-semibold">
                                        Cash
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->payment_reference)
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Payment Reference
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->payment_reference }}
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->paid_at)
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Paid At
                                    </div>
                                    <div class="fw-semibold">
                                        {{ \Carbon\Carbon::parse($withdrawal->paid_at)->format('d M Y H:i') }}
                                    </div>
                                </div>
                            @endif
                            @if($withdrawal->paid_by)
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        Paid By
                                    </div>
                                    <div class="fw-semibold">
                                        {{ optional($withdrawal->paidBy)->name ?? '—' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- LINKED TRANSACTION --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-exchange-alt me-1"></i>
                            Savings Transaction
                        </strong>
                    </div>
                    <div class="card-body">
                        @if($withdrawal->savingsTransaction)
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Transaction Number
                                    </div>
                                    <div class="fw-semibold">
                                        {{ $withdrawal->savingsTransaction->transaction_number }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Transaction Type
                                    </div>
                                    <div>
                                        {{ ucwords(str_replace('_', ' ', $withdrawal->savingsTransaction->transaction_type)) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Direction
                                    </div>
                                    <div>
                                        {{ ucfirst($withdrawal->savingsTransaction->direction) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Amount
                                    </div>
                                    <div class="fw-bold">
                                        KES
                                        {{ number_format($withdrawal->savingsTransaction->amount, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Running Balance
                                    </div>
                                    <div>
                                        KES
                                        {{ number_format($withdrawal->savingsTransaction->running_balance, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        Transaction Status
                                    </div>
                                    <span class="badge
                                        {{ $withdrawal->savingsTransaction->status === 'confirmed'
                                            ? 'bg-success'
                                            : ($withdrawal->savingsTransaction->status === 'pending'
                                                ? 'bg-warning text-dark'
                                                : 'bg-danger') }}">
                                        {{ ucfirst($withdrawal->savingsTransaction->status) }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                No linked savings transaction was found.
                            </div>
                        @endif
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- DECISION INFORMATION --}}
                {{-- ============================================================ --}}
                @if(
                    $withdrawal->approved_at ||
                    $withdrawal->decision_reason
                )
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white">
                            <strong>
                                <i class="fa fa-gavel me-1"></i>
                                Decision Information
                            </strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @if($withdrawal->approved_at)
                                    <div class="col-md-4">
                                        <div class="text-muted small">
                                            Approved At
                                        </div>
                                        <div>
                                            {{ \Carbon\Carbon::parse($withdrawal->approved_at)->format('d M Y H:i') }}
                                        </div>
                                    </div>
                                @endif
                                @if($withdrawal->approved_by)
                                    <div class="col-md-4">
                                        <div class="text-muted small">
                                            Approved By
                                        </div>
                                        <div>
                                            {{ optional($withdrawal->approvedBy)->name ?? '—' }}
                                        </div>
                                    </div>
                                @endif
                                @if($withdrawal->decision_reason)
                                    <div class="col-12">
                                        <div class="text-muted small">
                                            Decision Reason
                                        </div>
                                        <div class="border rounded p-3 bg-light">
                                            {{ $withdrawal->decision_reason }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            {{-- ================================================================ --}}
            {{-- RIGHT SIDEBAR --}}
            {{-- ================================================================ --}}
            <div class="col-lg-4">
                {{-- ============================================================ --}}
                {{-- WITHDRAWAL SUMMARY --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            Withdrawal Summary
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small">
                            Requested Amount
                        </div>
                        <div class="display-6 fw-bold mb-3">
                            KES {{ number_format($withdrawal->amount, 2) }}
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                Status
                            </span>
                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($withdrawal->status) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                Payment
                            </span>
                            <strong>
                                {{ ucwords(str_replace('_', ' ', $withdrawal->payment_method)) }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>
                                Requested
                            </span>
                            <strong>
                                {{ \Carbon\Carbon::parse($withdrawal->requested_date)->format('d M Y') }}
                            </strong>
                        </div>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- WORKFLOW --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            <i class="fa fa-history me-1"></i>
                            Workflow
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>Requested</strong>
                                <span class="text-success">
                                    <i class="fa fa-check-circle"></i>
                                </span>
                            </div>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($withdrawal->requested_date)->format('d M Y') }}
                            </small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong>Approved</strong>
                                @if($withdrawal->approved_at)
                                    <span class="text-success">
                                        <i class="fa fa-check-circle"></i>
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="fa fa-circle"></i>
                                    </span>
                                @endif
                            </div>
                            @if($withdrawal->approved_at)
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($withdrawal->approved_at)->format('d M Y H:i') }}
                                </small>
                            @else
                                <small class="text-muted">
                                    Awaiting approval
                                </small>
                            @endif
                        </div>
                        <div>
                            <div class="d-flex justify-content-between">
                                <strong>Paid</strong>
                                @if($withdrawal->paid_at)
                                    <span class="text-success">
                                        <i class="fa fa-check-circle"></i>
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="fa fa-circle"></i>
                                    </span>
                                @endif
                            </div>
                            @if($withdrawal->paid_at)
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($withdrawal->paid_at)->format('d M Y H:i') }}
                                </small>
                            @else
                                <small class="text-muted">
                                    Awaiting payment
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- REQUESTED BY --}}
                {{-- ============================================================ --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <strong>
                            Request Information
                        </strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">
                                Requested By
                            </div>
                            <div class="fw-semibold">
                                {{ optional($withdrawal->requestedBy)->name ?? '—' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">
                                Created
                            </div>
                            <div>
                                {{ $withdrawal->created_at->format('d M Y H:i') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">
                                Last Updated
                            </div>
                            <div>
                                {{ $withdrawal->updated_at->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- APPROVE MODAL --}}
    {{-- ================================================================ --}}

    @if($withdrawal->status === 'pending')
        <div class="modal fade"
             id="approveModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Approve Withdrawal
                        </h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Are you sure you want to approve this withdrawal?
                        </p>
                        <div class="alert alert-warning">
                            <strong>
                                KES {{ number_format($withdrawal->amount, 2) }}
                            </strong>
                            will be posted against savings account
                            <strong>
                                {{ $withdrawal->savingsAccount->account_number ?? '' }}
                            </strong>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <form method="POST"
                              action="{{ route('savings_withdrawals.approve', $withdrawal->id) }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-success">
                                <i class="fa fa-check me-1"></i>
                                Confirm Approval
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        {{-- ================================================================ --}}
        {{-- REJECT MODAL --}}
        {{-- ================================================================ --}}

        <div class="modal fade"
             id="rejectModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('savings_withdrawals.reject', $withdrawal->id) }}"
                      class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Reject Withdrawal
                        </h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">
                            Reason for Rejection
                        </label>
                        <textarea name="decision_reason"
                                  class="form-control"
                                  rows="4"
                                  required
                                  placeholder="Enter reason for rejecting this withdrawal..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit"
                                class="btn btn-danger">
                            Reject Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif


    {{-- ================================================================ --}}
    {{-- PAYMENT MODAL --}}
    {{-- ================================================================ --}}

    @if($withdrawal->status === 'approved')
        <div class="modal fade"
             id="payModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('savings_withdrawals.pay', $withdrawal->id) }}"
                      class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Pay Withdrawal
                        </h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Payment amount:
                            <strong>
                                KES {{ number_format($withdrawal->amount, 2) }}
                            </strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Payment Reference
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="payment_reference"
                                   class="form-control"
                                   required
                                   maxlength="255"
                                   placeholder="Enter payment / voucher / transaction reference">
                        </div>
                        <div class="small text-muted">
                            Payment method:
                            <strong>
                                {{ ucwords(str_replace('_', ' ', $withdrawal->payment_method)) }}
                            </strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit"
                                class="btn btn-success">
                            <i class="fa fa-money-bill-wave me-1"></i>
                            Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('script')
<script>
$(document).ready(function () {
    /*
     * Approval modal
     */
    $('#approveBtn').on('click', function () {
        $('#approveModal').modal('show');
    });

    /*
     * Rejection modal
     */
    $('#rejectBtn').on('click', function () {
        $('#rejectModal').modal('show');
    });

    /*
     * Payment modal
     */
    $('#payBtn').on('click', function () {
        $('#payModal').modal('show');
    });

    /*
     * Prevent accidental double submission.
     */
    $('form').on('submit', function () {
        var form = $(this);
        setTimeout(function () {
            form.find('button[type="submit"]')
                .prop('disabled', true);
        }, 10);
    });
});
</script>
@endsection