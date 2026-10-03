@extends('layouts.core')
@section('title', 'View | Share Accounts')

@section('content')
    @include('share_accounts.partial.header')    
    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">{{ $shareAccount->account_number }}</h4>
                <div class="text-muted">
                    {{ @$shareAccount->member->membership_number }} - {{ @$shareAccount->member->full_name }}
                </div>
            </div>
            <div class="d-flex gap-2">                
                @if($shareAccount->status !== 'closed')
                    <a href="{{ route('share_accounts.edit', $shareAccount->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                    @if($shareAccount->status === 'active')
                        <button type="button"
                                class="btn btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#closeAccountModal">
                            <i class="fa fa-lock me-1"></i> Close Account
                        </button>
                    @endif
                @endif
            </div>
        </div>
        {{-- Status --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Account Status</div>
                        @if($shareAccount->status === 'active')
                            <span class="badge bg-success px-3 py-2">
                                <i class="fa fa-check-circle me-1"></i> Active
                            </span>
                        @elseif($shareAccount->status === 'frozen')
                            <span class="badge bg-warning text-dark px-3 py-2">
                                <i class="fa fa-snowflake me-1"></i> Frozen
                            </span>
                        @else
                            <span class="badge bg-secondary px-3 py-2">
                                <i class="fa fa-lock me-1"></i> Closed
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Total Units</div>
                        <h4 class="fw-bold mb-0">
                            {{ number_format($shareAccount->total_units) }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Share Balance</div>
                        <h4 class="fw-bold mb-0">
                            KES {{ number_format($shareAccount->share_balance, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Available Amount</div>
                        <h4 class="fw-bold mb-0">
                            KES {{ number_format($shareAccount->available_amount, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            {{-- Main --}}
            <div class="col-lg-8">
                {{-- Account Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-id-card text-primary me-2"></i>
                            Account Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Account Number</label>
                                <div class="fw-semibold">
                                    {{ $shareAccount->account_number }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Member</label>
                                <div class="fw-semibold">
                                    {{ optional($shareAccount->member)->name ?? 'Member #' . $shareAccount->member_id }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Share Product</label>
                                <div class="fw-semibold">
                                    {{ optional($shareAccount->shareProduct)->name ?? 'Product #' . $shareAccount->share_product_id }}
                                </div>
                                @if($shareAccount->shareProduct)
                                    <small class="text-muted">
                                        {{ $shareAccount->shareProduct->code }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">Opened Date</label>
                                <div class="fw-semibold">
                                    {{ optional($shareAccount->opened_date)->format('d M Y') ?? $shareAccount->opened_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Share Holding --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-chart-pie text-primary me-2"></i>
                            Share Holding
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Total Units</label>
                                <div class="fs-5 fw-bold">
                                    {{ number_format($shareAccount->total_units) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Share Balance</label>
                                <div class="fs-5 fw-bold">
                                    KES {{ number_format($shareAccount->share_balance, 2) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Held Amount</label>
                                <div class="fs-5 fw-bold">
                                    KES {{ number_format($shareAccount->held_amount, 2) }}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Available Amount
                                </label>
                                <div class="fs-4 fw-bold text-success">
                                    KES {{ number_format($shareAccount->available_amount, 2) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small d-block">
                                    Unit Value
                                </label>
                                <div class="fs-5 fw-semibold">
                                    KES {{ number_format(optional($shareAccount->shareProduct)->unit_value ?? 0, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Account Lifecycle --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-history text-primary me-2"></i>
                            Account Lifecycle
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Opened</label>
                                <div class="fw-semibold">
                                    {{ optional($shareAccount->opened_date)->format('d M Y') ?? $shareAccount->opened_date }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Status</label>
                                <div class="fw-semibold text-capitalize">
                                    {{ $shareAccount->status }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Created</label>
                                <div class="fw-semibold">
                                    {{ optional($shareAccount->created_at)->format('d M Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Product --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-cubes text-primary me-2"></i>
                            Share Product
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($shareAccount->shareProduct)
                            <div class="mb-3">
                                <div class="text-muted small">Product</div>
                                <div class="fw-semibold">
                                    {{ $shareAccount->shareProduct->name }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Code</div>
                                <div class="fw-semibold">
                                    {{ $shareAccount->shareProduct->code }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small">Unit Value</div>
                                <div class="fw-semibold">
                                    KES {{ number_format($shareAccount->shareProduct->unit_value, 2) }}
                                </div>
                            </div>
                        @else
                            <div class="text-muted">
                                Share product information unavailable.
                            </div>
                        @endif
                    </div>
                </div>
                {{-- Account Controls --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-cogs text-primary me-2"></i>
                            Account Controls
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($shareAccount->status === 'active')
                            <div class="alert alert-warning border-0 small">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                Closing an account is a controlled operation and cannot be
                                performed through the normal edit workflow.
                            </div>
                            <button type="button"
                                    class="btn btn-outline-danger w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#closeAccountModal">
                                <i class="fa fa-lock me-1"></i>
                                Close Share Account
                            </button>
                        @elseif($shareAccount->status === 'frozen')
                            <div class="alert alert-warning border-0 small mb-0">
                                This account is currently frozen.
                            </div>
                        @else
                            <div class="alert alert-secondary border-0 small mb-0">
                                <i class="fa fa-lock me-1"></i>
                                This share account is closed.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Close Account Modal --}}
    @if($shareAccount->status !== 'closed')
        <div class="modal fade"
             id="closeAccountModal"
             tabindex="-1"
             aria-labelledby="closeAccountModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="closeAccountModalLabel">
                            Close Share Account
                        </h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>
                    </div>
                    {{ Form::open(['route' => ['share_accounts.close', $shareAccount->id], 'method' => 'POST', 'id' => 'closeAccountForm']) }}                        
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                <strong>This action is controlled.</strong>
                                The account will no longer be available for normal share transactions.
                            </div>
                            <div class="bg-light rounded p-3 mb-3">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Account
                                        </small>
                                        <strong>
                                            {{ $shareAccount->account_number }}
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Current Status
                                        </small>
                                        <strong class="text-capitalize">
                                            {{ $shareAccount->status }}
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Share Balance
                                        </small>
                                        <strong>
                                            KES {{ number_format($shareAccount->share_balance, 2) }}
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">
                                            Held Amount
                                        </small>
                                        <strong>
                                            KES {{ number_format($shareAccount->held_amount, 2) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="closure_reason" class="form-label">
                                    Closure Reason <span class="text-danger">*</span>
                                </label>
                                <textarea name="closure_reason"
                                          id="closure_reason"
                                          rows="4"
                                          class="form-control"
                                          placeholder="Enter the reason for closing this account..."
                                          required></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       value="1"
                                       id="confirm_closure"
                                       required>
                                <label class="form-check-label" for="confirm_closure">
                                    I confirm that this share account should be closed.
                                </label>
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
                                    id="closeAccountBtn">
                                <i class="fa fa-lock me-1"></i>
                                Close Account
                            </button>
                        </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    @endif
@endsection


@section('script')
<script>
$(document).ready(function () {

    $('#closeAccountForm').on('submit', function (e) {
        const reason = $.trim($('#closure_reason').val());

        if (!reason) {
            e.preventDefault();
            alert('Please provide a closure reason.');
            $('#closure_reason').focus();
            return false;
        }

        if (!$('#confirm_closure').is(':checked')) {
            e.preventDefault();
            alert('Please confirm the account closure.');
            return false;
        }

        $('#closeAccountBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> Closing...');
    });

});
</script>
@endsection