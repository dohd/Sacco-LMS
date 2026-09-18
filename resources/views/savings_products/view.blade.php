@extends('layouts.core')
@section('title', 'View | Savings Products')

@section('content')
    @include('savings_products.partial.header')    
    <div class="container-fluid py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="mb-1">Savings Product</h4>
                <div class="text-muted small">View savings product configuration and rules</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('savings_products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('savings_products.edit', $savingsProduct->id) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-edit me-1"></i> Edit
                </a>
            </div>
        </div>

        <div class="row g-3">
            {{-- Product Overview --}}
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Product Overview</h6>
                        @if($savingsProduct->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <div class="text-muted small">Product Code</div>
                                <div class="fw-semibold">{{ $savingsProduct->code }}</div>
                            </div>
                            <div class="col-md-5 col-sm-6">
                                <div class="text-muted small">Product Name</div>
                                <div class="fw-semibold">{{ $savingsProduct->name }}</div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="text-muted small">Product Type</div>
                                <div>
                                    @if($savingsProduct->product_type === 'compulsory')
                                        <span class="badge bg-warning text-dark">Compulsory</span>
                                    @elseif($savingsProduct->product_type === 'voluntary')
                                        <span class="badge bg-info text-dark">Voluntary</span>
                                    @else
                                        <span class="badge bg-primary">Fixed Deposit</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Interest Configuration --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Interest Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Interest Rate</div>
                                <div class="fs-5 fw-semibold">
                                    {{ number_format($savingsProduct->interest_rate, 2) }}%
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Interest Frequency</div>
                                <div class="fw-semibold">
                                    {{ ucwords(str_replace('_', ' ', $savingsProduct->interest_frequency)) }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Calculation Method</div>
                                <div class="fw-semibold">
                                    {{ ucfirst($savingsProduct->interest_calculation_method) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Deposit Terms --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Deposit Terms & Contributions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Minimum Term</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->minimum_term_months }} months
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Maximum Term</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->maximum_term_months !== null
                                        ? $savingsProduct->maximum_term_months . ' months'
                                        : 'No limit' }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Minimum Balance</div>
                                <div class="fw-semibold">
                                    KES {{ number_format($savingsProduct->minimum_balance, 2) }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Minimum Monthly Contribution</div>
                                <div class="fw-semibold">
                                    KES {{ number_format($savingsProduct->minimum_monthly_contribution, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Withdrawal Rules --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Withdrawal Rules</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Withdrawals</div>
                                <div>
                                    @if($savingsProduct->allows_withdrawals)
                                        <span class="badge bg-success">Allowed</span>
                                    @else
                                        <span class="badge bg-danger">Not Allowed</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Notice Period</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->withdrawal_notice_days }} days
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Partial Withdrawals</div>
                                <div>
                                    @if($savingsProduct->allows_partial_withdrawals)
                                        <span class="badge bg-success">Allowed</span>
                                    @else
                                        <span class="badge bg-secondary">Not Allowed</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Premature Withdrawal</div>
                                <div>
                                    @if($savingsProduct->allows_premature_withdrawal)
                                        <span class="badge bg-success">Allowed</span>
                                    @else
                                        <span class="badge bg-secondary">Not Allowed</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Premature Withdrawal Penalty</div>
                                <div class="fw-semibold">
                                    {{ number_format($savingsProduct->premature_withdrawal_penalty_percentage, 2) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Maturity & Rollover --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Maturity & Rollover</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Automatic Rollover</div>
                                <div>
                                    @if($savingsProduct->auto_rollover)
                                        <span class="badge bg-success">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Rollover Option</div>
                                <div class="fw-semibold">
                                    @if($savingsProduct->rollover_option)
                                        {{ ucwords(str_replace('_', ' ', $savingsProduct->rollover_option)) }}
                                    @else
                                        Not applicable
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Limits --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Product Limits & Security</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Maximum Balance</div>
                                <div class="fw-semibold">
                                    @if($savingsProduct->maximum_balance !== null)
                                        KES {{ number_format($savingsProduct->maximum_balance, 2) }}
                                    @else
                                        No limit
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Can Secure Loan</div>
                                <div>
                                    @if($savingsProduct->can_secure_loan)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- GL Mapping --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">General Ledger Mapping</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="text-muted small">Savings Control Account</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->savings_control_account_id }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Interest Expense Account</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->interest_expense_account_id }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Fee Income Account</div>
                                <div class="fw-semibold">
                                    {{ $savingsProduct->fee_income_account_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Status --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Product Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Status</div>
                                <div>
                                    @if($savingsProduct->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Product ID</div>
                                <div class="fw-semibold">
                                    #{{ $savingsProduct->id }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Created</div>
                                <div class="fw-semibold">
                                    {{ optional($savingsProduct->created_at)->format('d M Y H:i') }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Last Updated</div>
                                <div class="fw-semibold">
                                    {{ optional($savingsProduct->updated_at)->format('d M Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Summary --}}
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Product Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-3">
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Interest Rate</div>
                                    <div class="fs-4 fw-bold">
                                        {{ number_format($savingsProduct->interest_rate, 2) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Minimum Balance</div>
                                    <div class="fs-5 fw-bold">
                                        KES {{ number_format($savingsProduct->minimum_balance, 0) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Maximum Balance</div>
                                    <div class="fs-5 fw-bold">
                                        {{ $savingsProduct->maximum_balance !== null
                                            ? 'KES ' . number_format($savingsProduct->maximum_balance, 0)
                                            : 'Unlimited' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Loan Security</div>
                                    <div class="fs-5 fw-bold">
                                        {{ $savingsProduct->can_secure_loan ? 'Allowed' : 'Not Allowed' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(document).ready(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endsection