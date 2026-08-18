@extends('layouts.core')
@section('title', 'Loan Products')

@section('content')
    @include('loan_products.partial.header')
    <style>
        body {
            background: #f4f6f9;
        }

        .nomination-container {
            max-width: 1150px;
        }

        .form-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .form-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 18px 22px;
        }

        .form-card .card-body {
            padding: 24px;
        }

        .form-header {
            background: #198754;
            color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-right: 10px;
            border-radius: 50%;
            background: #198754;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .nominee-card,
        .witness-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .nominee-card .card-header,
        .witness-card .card-header {
            background: #f8f9fa;
            padding: 12px 16px;
        }

        .percentage-summary {
            position: sticky;
            top: 15px;
            z-index: 5;
        }

        .signature-preview {
            display: none;
            width: 100%;
            max-width: 220px;
            height: 120px;
            margin-top: 10px;
            padding: 4px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: contain;
            background: #ffffff;
        }

        .submit-section {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding: 15px 0;
            background: rgba(244, 246, 249, 0.95);
        }
    </style>
    <div class="container-fluid">
        <form method="POST"
              action="{{ isset($loanProduct) ? route('loan_products.update', $loanProduct->id) : route('loan_products.store') }}"
              id="loanProductForm">
            @csrf
            @if(isset($loanProduct))
                @method('PUT')
            @endif
            <div class="row">
                <div class="col-lg-12">
                    <!-- ============================= -->
                    <!-- BASIC PRODUCT INFORMATION -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                Basic Product Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Product Code
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           name="code"
                                           class="form-control"
                                           maxlength="50"
                                           value="{{ old('code', $loanProduct->code ?? '') }}"
                                           required>
                                    <small class="text-muted">
                                        Example: DEV, EMG, SCH
                                    </small>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">
                                        Product Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           name="name"
                                           class="form-control"
                                           value="{{ old('name', $loanProduct->name ?? '') }}"
                                           required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">
                                        Active
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="is_active"
                                               name="is_active"
                                               value="1"
                                               {{ old('is_active', $loanProduct->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                               for="is_active">
                                            Yes
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">
                                        Status
                                    </label>
                                    <div class="form-control bg-light">
                                        {{ old('is_active', $loanProduct->is_active ?? true) ? 'Active' : 'Inactive' }}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">
                                        Description
                                    </label>
                                    <textarea name="description"
                                              rows="3"
                                              class="form-control">{{ old('description', $loanProduct->description ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Effective From
                                    </label>
                                    <input type="date"
                                           class="form-control"
                                           name="effective_from"
                                           value="{{ old('effective_from', optional($loanProduct->effective_from ?? null)->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Effective To
                                    </label>
                                    <input type="date"
                                           class="form-control"
                                           name="effective_to"
                                           value="{{ old('effective_to', optional($loanProduct->effective_to ?? null)->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ============================= -->
                    <!-- GENERAL LEDGER MAPPING -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                General Ledger Mapping
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Every loan product should map to the appropriate
                                General Ledger accounts for automated accounting.
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Loan Principal Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="loan_principal_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('loan_principal_account_id', $loanProduct->loan_principal_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Interest Receivable Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="interest_receivable_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('interest_receivable_account_id', $loanProduct->interest_receivable_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Interest Income Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="interest_income_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('interest_income_account_id', $loanProduct->interest_income_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Penalty Receivable Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="penalty_receivable_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('penalty_receivable_account_id', $loanProduct->penalty_receivable_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Penalty Income Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="penalty_income_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('penalty_income_account_id', $loanProduct->penalty_income_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Processing Fee Income Account
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="processing_fee_income_account_id"
                                            class="form-select"
                                            required>
                                        <option value="">
                                            -- Select Account --
                                        </option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('processing_fee_income_account_id', $loanProduct->processing_fee_income_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }}
                                                -
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Continue with Loan Configuration (Part 2) -->


                    <!-- ============================= -->
                    <!-- LOAN CONFIGURATION -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">
                                Loan Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Minimum Loan Amount
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="minimum_amount"
                                           class="form-control text-end"
                                           value="{{ old('minimum_amount', $loanProduct->minimum_amount ?? 0) }}"
                                           required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Maximum Loan Amount
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="maximum_amount"
                                           class="form-control text-end"
                                           value="{{ old('maximum_amount', $loanProduct->maximum_amount ?? '') }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Minimum Repayment (Months)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           min="1"
                                           name="minimum_repayment_months"
                                           class="form-control"
                                           value="{{ old('minimum_repayment_months', $loanProduct->minimum_repayment_months ?? 1) }}"
                                           required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Maximum Repayment (Months)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           min="1"
                                           name="maximum_repayment_months"
                                           class="form-control"
                                           value="{{ old('maximum_repayment_months', $loanProduct->maximum_repayment_months ?? '') }}"
                                           required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Interest Rate (%)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                               step="0.0001"
                                               min="0"
                                               name="interest_rate"
                                               class="form-control text-end"
                                               value="{{ old('interest_rate', $loanProduct->interest_rate ?? '') }}"
                                               required>
                                        <span class="input-group-text">
                                            %
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Interest Method
                                    </label>
                                    <select name="interest_method"
                                            class="form-select">
                                        <option value="reducing_balance"
                                            {{ old('interest_method', $loanProduct->interest_method ?? '') == 'reducing_balance' ? 'selected' : '' }}>
                                            Reducing Balance
                                        </option>
                                        <option value="flat_rate"
                                            {{ old('interest_method', $loanProduct->interest_method ?? '') == 'flat_rate' ? 'selected' : '' }}>
                                            Flat Rate
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Interest Frequency
                                    </label>
                                    <select name="interest_frequency"
                                            class="form-select">
                                        <option value="annual"
                                            {{ old('interest_frequency', $loanProduct->interest_frequency ?? '') == 'annual' ? 'selected' : '' }}>
                                            Annual
                                        </option>
                                        <option value="monthly"
                                            {{ old('interest_frequency', $loanProduct->interest_frequency ?? '') == 'monthly' ? 'selected' : '' }}>
                                            Monthly
                                        </option>
                                        <option value="one_time"
                                            {{ old('interest_frequency', $loanProduct->interest_frequency ?? '') == 'one_time' ? 'selected' : '' }}>
                                            One Time
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Grace Period (Days)
                                    </label>
                                    <input type="number"
                                           min="0"
                                           name="grace_period_days"
                                           class="form-control"
                                           value="{{ old('grace_period_days', $loanProduct->grace_period_days ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ============================= -->
                    <!-- GUARANTOR CONFIGURATION -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                Guarantor Requirements
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Requires Guarantors
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="requires_guarantors"
                                               name="requires_guarantors"
                                               value="1"
                                               {{ old('requires_guarantors', $loanProduct->requires_guarantors ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                               for="requires_guarantors">
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="guarantorSection">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Minimum Guarantors
                                        </label>
                                        <input type="number"
                                               min="0"
                                               name="minimum_guarantors"
                                               class="form-control"
                                               value="{{ old('minimum_guarantors', $loanProduct->minimum_guarantors ?? 1) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">
                                            Maximum Guarantors
                                        </label>
                                        <input type="number"
                                               min="1"
                                               name="maximum_guarantors"
                                               class="form-control"
                                               value="{{ old('maximum_guarantors', $loanProduct->maximum_guarantors ?? '') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            Minimum Coverage (%)
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                   step="0.0001"
                                                   min="0"
                                                   name="minimum_guarantor_coverage_percentage"
                                                   class="form-control text-end"
                                                   value="{{ old('minimum_guarantor_coverage_percentage', $loanProduct->minimum_guarantor_coverage_percentage ?? 100) }}">
                                            <span class="input-group-text">
                                                %
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-light border">
                                    The total guaranteed amount provided by all
                                    guarantors must satisfy the minimum coverage
                                    percentage configured above before a loan can
                                    be approved.
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Continue with Eligibility Rules (Part 3) -->


                    <!-- ============================= -->
                    <!-- ELIGIBILITY RULES -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                Member Eligibility
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Minimum Membership (Months)
                                    </label>
                                    <input type="number"
                                           name="minimum_membership_months"
                                           min="0"
                                           class="form-control"
                                           value="{{ old('minimum_membership_months', $loanProduct->minimum_membership_months ?? 0) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Minimum Share Contribution
                                    </label>
                                    <input type="number"
                                           name="minimum_share_contribution"
                                           step="0.01"
                                           min="0"
                                           class="form-control text-end"
                                           value="{{ old('minimum_share_contribution', $loanProduct->minimum_share_contribution ?? 0) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Minimum Monthly Contribution
                                    </label>
                                    <input type="number"
                                           name="minimum_monthly_contribution"
                                           step="0.01"
                                           min="0"
                                           class="form-control text-end"
                                           value="{{ old('minimum_monthly_contribution', $loanProduct->minimum_monthly_contribution ?? 0) }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Share Multiplier
                                    </label>
                                    <input type="number"
                                           name="share_multiplier"
                                           step="0.01"
                                           min="0"
                                           class="form-control text-end"
                                           value="{{ old('share_multiplier', $loanProduct->share_multiplier ?? '') }}">
                                    <small class="text-muted">
                                        Example: 3.00 = 3 × member shares
                                    </small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Maximum Active Loans
                                    </label>
                                    <input type="number"
                                           min="0"
                                           name="maximum_active_loans"
                                           class="form-control"
                                           value="{{ old('maximum_active_loans', $loanProduct->maximum_active_loans ?? '') }}">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Allows Top-up
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="allows_top_up"
                                               name="allows_top_up"
                                               value="1"
                                               {{ old('allows_top_up', $loanProduct->allows_top_up ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                               for="allows_top_up">
                                            Yes
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Allows Refinancing
                                    </label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="allows_refinancing"
                                               name="allows_refinancing"
                                               value="1"
                                               {{ old('allows_refinancing', $loanProduct->allows_refinancing ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                               for="allows_refinancing">
                                            Yes
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3"
                                     id="topupPercentageContainer">
                                    <label class="form-label">
                                        Minimum Repaid (%)
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                               name="minimum_repaid_percentage_for_top_up"
                                               step="0.0001"
                                               min="0"
                                               max="100"
                                               class="form-control text-end"
                                               value="{{ old('minimum_repaid_percentage_for_top_up', $loanProduct->minimum_repaid_percentage_for_top_up ?? '') }}">
                                        <span class="input-group-text">
                                            %
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">
                                        Advanced Eligibility Rules (JSON)
                                    </label>
                                    <textarea name="eligibility_rules"
                                              rows="10"
                                              class="form-control font-monospace">{{ old('eligibility_rules', isset($loanProduct->eligibility_rules) ? json_encode($loanProduct->eligibility_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                                    <small class="text-muted">
                                        Example:
                                        {
                                            "minimum_age":18,
                                            "maximum_age_at_maturity":65,
                                            "allowed_employment_types":[
                                                "permanent",
                                                "contract"
                                            ],
                                            "requires_salary_checkoff":false
                                        }
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ============================= -->
                    <!-- PRODUCT CHARGES -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                Charges & Fees
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Application Fee
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="application_fee"
                                           class="form-control text-end"
                                           value="{{ old('application_fee', $loanProduct->application_fee ?? 0) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Processing Fee (%)
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                               step="0.0001"
                                               min="0"
                                               name="processing_fee_percentage"
                                               class="form-control text-end"
                                               value="{{ old('processing_fee_percentage', $loanProduct->processing_fee_percentage ?? 0) }}">
                                        <span class="input-group-text">
                                            %
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">
                                        Insurance Fee (%)
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                               step="0.0001"
                                               min="0"
                                               name="insurance_fee_percentage"
                                               class="form-control text-end"
                                               value="{{ old('insurance_fee_percentage', $loanProduct->insurance_fee_percentage ?? 0) }}">
                                        <span class="input-group-text">
                                            %
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning mb-0">
                                These charges are product defaults. They may be
                                overridden during loan processing if your business
                                rules permit.
                            </div>
                        </div>
                    </div>
                    <!-- Continue with Form Footer & jQuery (Part 4) -->

                    <!-- ============================= -->
                    <!-- FORM ACTIONS -->
                    <!-- ============================= -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    Fields marked with
                                    <span class="text-danger">*</span>
                                    are required.
                                </div>
                                <div>
                                    <a href="{{ route('loan_products.index') }}"
                                       class="btn btn-light">
                                        Cancel
                                    </a>
                                    <button type="submit"
                                            name="action"
                                            value="save"
                                            class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Save Product
                                    </button>
                                    <button type="submit"
                                            name="action"
                                            value="save_close"
                                            class="btn btn-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Save & Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>   
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    function toggleGuarantors() {
        if ($('#requires_guarantors').is(':checked')) {

            $('#guarantorSection').slideDown(200);

        } else {

            $('#guarantorSection').slideUp(200);

        }
    }

    function toggleTopUp() {
        if ($('#allows_top_up').is(':checked')) {

            $('#topupPercentageContainer').slideDown(200);

        } else {

            $('#topupPercentageContainer').slideUp(200);

            $('input[name="minimum_repaid_percentage_for_top_up"]')
                .val('');

        }
    }

    toggleGuarantors();

    toggleTopUp();

    $('#requires_guarantors').change(function () {

        toggleGuarantors();

    });

    $('#allows_top_up').change(function () {

        toggleTopUp();

    });

    $('#loanProductForm').submit(function () {

        let minAmount = parseFloat(
            $('input[name="minimum_amount"]').val() || 0
        );

        let maxAmount = parseFloat(
            $('input[name="maximum_amount"]').val() || 0
        );

        if (maxAmount > 0 && maxAmount < minAmount) {

            alert(
                'Maximum loan amount cannot be less than the minimum loan amount.'
            );

            return false;

        }

        let minMonths = parseInt(
            $('input[name="minimum_repayment_months"]').val() || 0
        );

        let maxMonths = parseInt(
            $('input[name="maximum_repayment_months"]').val() || 0
        );

        if (maxMonths < minMonths) {

            alert(
                'Maximum repayment period cannot be less than the minimum repayment period.'
            );

            return false;

        }

        if ($('#requires_guarantors').is(':checked')) {

            let minGuarantors = parseInt(
                $('input[name="minimum_guarantors"]').val() || 0
            );

            let maxGuarantors = parseInt(
                $('input[name="maximum_guarantors"]').val() || 0
            );

            if (maxGuarantors > 0 &&
                maxGuarantors < minGuarantors) {

                alert(
                    'Maximum guarantors cannot be less than minimum guarantors.'
                );

                return false;

            }

        }

        if ($('#allows_top_up').is(':checked')) {

            let repaid = parseFloat(
                $('input[name="minimum_repaid_percentage_for_top_up"]')
                .val() || 0
            );

            if (repaid < 0 || repaid > 100) {

                alert(
                    'Minimum repaid percentage must be between 0 and 100.'
                );

                return false;

            }

        }

        let json = $('textarea[name="eligibility_rules"]').val().trim();

        if (json.length > 0) {

            try {

                JSON.parse(json);

            } catch (e) {

                alert('Eligibility Rules contains invalid JSON.');

                return false;

            }

        }

        return true;

    });
});
</script>
@endsection
