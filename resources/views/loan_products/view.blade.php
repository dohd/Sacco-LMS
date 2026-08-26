@extends('layouts.core')
@section('title', 'Loan Products | View')
    

@section('content')
@include('loan_products.partial.header')
<div class="container-fluid">
    @php
        $statusClass = $loanProduct->is_active ? 'success' : 'secondary';
    @endphp

    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center p-3">
            <div>
                <h4 class="mb-1">{{ $loanProduct->name }}</h4>
                <div class="text-muted">
                    Product Code: <strong>{{ $loanProduct->code }}</strong>
                </div>
            </div>

            <div class="text-end">
                <span class="badge bg-{{ $statusClass }} fs-6">
                    {{ $loanProduct->is_active ? 'Active' : 'Inactive' }}
                </span>

                @if($loanProduct->effective_from)
                    <div class="small text-muted mt-2">
                        Effective from {{ \Carbon\Carbon::parse($loanProduct->effective_from)->format('d M Y') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-9">

            <!-- Basic Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Product Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Product Code</small>
                            <strong>{{ $loanProduct->code }}</strong>
                        </div>

                        <div class="col-md-5 mb-3">
                            <small class="text-muted d-block">Product Name</small>
                            <strong>{{ $loanProduct->name }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-{{ $statusClass }}">
                                {{ $loanProduct->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="col-md-12">
                            <small class="text-muted d-block">Description</small>
                            <div>
                                {!! nl2br(e($loanProduct->description ?: 'No description provided.')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loan Limits -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Loan Limits & Repayment</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Minimum Amount</small>
                            <strong>KES {{ number_format($loanProduct->minimum_amount, 2) }}</strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Maximum Amount</small>
                            <strong>
                                {{ $loanProduct->maximum_amount !== null
                                    ? 'KES ' . number_format($loanProduct->maximum_amount, 2)
                                    : 'No Limit' }}
                            </strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Minimum Repayment</small>
                            <strong>{{ $loanProduct->minimum_repayment_months }} Months</strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">Maximum Repayment</small>
                            <strong>{{ $loanProduct->maximum_repayment_months }} Months</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interest -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Interest Configuration</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Interest Rate</small>
                            <h5 class="mb-0">{{ +$loanProduct->interest_rate }}%</h5>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Interest Method</small>
                            <strong>{{ ucwords(str_replace('_', ' ', $loanProduct->interest_method)) }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Interest Frequency</small>
                            <strong>{{ ucwords(str_replace('_', ' ', $loanProduct->interest_frequency)) }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Grace Period</small>
                            <strong>{{ $loanProduct->grace_period_days }} Days</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guarantor Requirements -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Guarantor Requirements</h5>
                </div>

                <div class="card-body">
                    @if($loanProduct->requires_guarantors)
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <small class="text-muted d-block">Requires Guarantors</small>
                                <span class="badge bg-success">Yes</span>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted d-block">Minimum Guarantors</small>
                                <strong>{{ $loanProduct->minimum_guarantors }}</strong>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted d-block">Maximum Guarantors</small>
                                <strong>{{ $loanProduct->maximum_guarantors ?? 'No Limit' }}</strong>
                            </div>

                            <div class="col-md-3 mb-3">
                                <small class="text-muted d-block">Minimum Coverage</small>
                                <strong>{{ number_format($loanProduct->minimum_guarantor_coverage_percentage, 2) }}%</strong>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border mb-0">
                            This loan product does not require guarantors.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Eligibility -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Member Eligibility</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Minimum Membership Period</small>
                            <strong>{{ $loanProduct->minimum_membership_months }} Months</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Minimum Share Contribution</small>
                            <strong>KES {{ number_format($loanProduct->minimum_share_contribution, 2) }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Minimum Monthly Contribution</small>
                            <strong>KES {{ number_format($loanProduct->minimum_monthly_contribution, 2) }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Share Multiplier</small>
                            <strong>
                                {{ $loanProduct->share_multiplier !== null
                                    ? number_format($loanProduct->share_multiplier, 2) . ' ×'
                                    : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Maximum Active Loans</small>
                            <strong>{{ $loanProduct->maximum_active_loans ?? 'No Limit' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top-Up & Refinancing -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Top-Up & Refinancing</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Allows Top-Up</small>

                            @if($loanProduct->allows_top_up)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Allows Refinancing</small>

                            @if($loanProduct->allows_refinancing)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Minimum Repaid Before Top-Up</small>
                            <strong>
                                {{ $loanProduct->allows_top_up && $loanProduct->minimum_repaid_percentage_for_top_up !== null
                                    ? number_format($loanProduct->minimum_repaid_percentage_for_top_up, 2) . '%'
                                    : '-' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fees -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Charges & Fees</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Application Fee</small>
                            <strong>KES {{ number_format($loanProduct->application_fee, 2) }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Processing Fee</small>
                            <strong>{{ number_format($loanProduct->processing_fee_percentage, 4) }}%</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Insurance Fee</small>
                            <strong>{{ number_format($loanProduct->insurance_fee_percentage, 4) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eligibility JSON -->
            @if($loanProduct->eligibility_rules)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Advanced Eligibility Rules</h5>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="collapse"
                                data-bs-target="#eligibilityRules">
                            Show / Hide
                        </button>
                    </div>

                    <div class="collapse" id="eligibilityRules">
                        <div class="card-body">
                            <pre class="bg-light border rounded p-3 mb-0">{{ json_encode($loanProduct->eligibility_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">

            <!-- Product Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Product Summary</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ $loanProduct->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Interest</small>
                        <strong>{{ number_format($loanProduct->interest_rate, 2) }}%</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Maximum Loan</small>
                        <strong>
                            {{ $loanProduct->maximum_amount !== null
                                ? 'KES ' . number_format($loanProduct->maximum_amount, 2)
                                : 'Unlimited' }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Maximum Term</small>
                        <strong>{{ $loanProduct->maximum_repayment_months }} Months</strong>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted d-block">Guarantors</small>
                        <strong>{{ $loanProduct->requires_guarantors ? 'Required' : 'Not Required' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Effective Period -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Effective Period</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Effective From</small>
                        <strong>
                            {{ $loanProduct->effective_from
                                ? \Carbon\Carbon::parse($loanProduct->effective_from)->format('d M Y')
                                : '-' }}
                        </strong>
                    </div>

                    <div>
                        <small class="text-muted d-block">Effective To</small>
                        <strong>
                            {{ $loanProduct->effective_to
                                ? \Carbon\Carbon::parse($loanProduct->effective_to)->format('d M Y')
                                : 'No Expiry' }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- GL Mapping -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">GL Mapping</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Loan Principal</small>
                        <strong>
                            {{ $loanProduct->loanPrincipalAccount->account_code ?? $loanProduct->loan_principal_account_id }}
                        </strong>
                        <div class="small text-muted">
                            {{ $loanProduct->loanPrincipalAccount->account_name ?? '' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Interest Receivable</small>
                        <strong>
                            {{ $loanProduct->interestReceivableAccount->account_code ?? $loanProduct->interest_receivable_account_id }}
                        </strong>
                        <div class="small text-muted">
                            {{ $loanProduct->interestReceivableAccount->account_name ?? '' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Interest Income</small>
                        <strong>
                            {{ $loanProduct->interestIncomeAccount->account_code ?? $loanProduct->interest_income_account_id }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Penalty Receivable</small>
                        <strong>
                            {{ $loanProduct->penaltyReceivableAccount->account_code ?? $loanProduct->penalty_receivable_account_id }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Penalty Income</small>
                        <strong>
                            {{ $loanProduct->penaltyIncomeAccount->account_code ?? $loanProduct->penalty_income_account_id }}
                        </strong>
                    </div>

                    <div>
                        <small class="text-muted d-block">Processing Fee Income</small>
                        <strong>
                            {{ $loanProduct->processingFeeIncomeAccount->account_code ?? $loanProduct->processing_fee_income_account_id }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>

                <div class="card-body d-grid gap-2">
                    <a href="{{ route('loan_products.index') }}" class="btn btn-light">
                        Back to Loan Products
                    </a>

                    <a href="{{ route('loan_products.edit', $loanProduct->id) }}" class="btn btn-outline-primary">
                        Edit Product
                    </a>

                    <button type="button"
                            class="btn {{ $loanProduct->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#statusModal">
                        {{ $loanProduct->is_active ? 'Deactivate Product' : 'Activate Product' }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST"
              action="{{ route('loan_products.status', $loanProduct->id) }}"
              class="modal-content"
              id="productStatusForm">

            @csrf
            @method('PATCH')

            <input type="hidden" name="is_active" value="{{ $loanProduct->is_active ? 0 : 1 }}">

            <div class="modal-header">
                <h5 class="modal-title">
                    {{ $loanProduct->is_active ? 'Deactivate' : 'Activate' }} Loan Product
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if($loanProduct->is_active)
                    <div class="alert alert-warning mb-0">
                        New loan applications should no longer use this product once it is deactivated.
                        Existing loans should remain unaffected.
                    </div>
                @else
                    <p class="mb-0">
                        Activate <strong>{{ $loanProduct->name }}</strong> for new loan applications?
                    </p>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit"
                        class="btn {{ $loanProduct->is_active ? 'btn-danger' : 'btn-success' }}">
                    {{ $loanProduct->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
$(function () {
    $('#productStatusForm').on('submit', function () {
        const button = $(this).find('button[type="submit"]');

        if (button.prop('disabled')) {
            return false;
        }

        button.prop('disabled', true);
        button.text('Processing...');
        return true;
    });
});
</script>
@endpush