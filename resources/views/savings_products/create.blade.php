@extends('layouts.core')
@section('title', 'Savings Products')

@section('content')
    @include('savings_products.partial.header')
    <div class="container-fluid">
        <form method="POST"
              action="{{ isset($savingsProduct) ? route('savings_products.update', $savingsProduct->id) : route('savings_products.store') }}"
              id="savingsProductForm">

            @csrf
            @if(isset($savingsProduct))
                @method('PUT')
            @endif

            <!-- Basic Product Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Savings Product Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">
                                Product Code <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="code"
                                   maxlength="50"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $savingsProduct->code ?? '') }}"
                                   required>

                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                Example: COMP, VOL, FD
                            </small>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">
                                Product Name <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $savingsProduct->name ?? '') }}"
                                   required>

                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Product Type <span class="text-danger">*</span>
                            </label>

                            <select name="product_type"
                                    id="product_type"
                                    class="form-select @error('product_type') is-invalid @enderror"
                                    required>

                                <option value="">Select Product Type</option>

                                <option value="compulsory"
                                    {{ old('product_type', $savingsProduct->product_type ?? '') === 'compulsory' ? 'selected' : '' }}>
                                    Compulsory Savings
                                </option>

                                <option value="voluntary"
                                    {{ old('product_type', $savingsProduct->product_type ?? '') === 'voluntary' ? 'selected' : '' }}>
                                    Voluntary Savings
                                </option>

                                <option value="fixed_deposit"
                                    {{ old('product_type', $savingsProduct->product_type ?? '') === 'fixed_deposit' ? 'selected' : '' }}>
                                    Fixed Deposit
                                </option>
                            </select>

                            @error('product_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product Status</label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $savingsProduct->is_active ?? true) ? 'checked' : '' }}>

                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Status</label>

                            <input type="text"
                                   id="productStatus"
                                   class="form-control bg-light"
                                   value="{{ old('is_active', $savingsProduct->is_active ?? true) ? 'Active' : 'Inactive' }}"
                                   readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Ledger Mapping -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">General Ledger Mapping</h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        Configure the accounts used for automatic savings, interest, and fee postings.
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Savings Control Account <span class="text-danger">*</span>
                            </label>

                            <select name="savings_control_account_id"
                                    class="form-select @error('savings_control_account_id') is-invalid @enderror"
                                    required>

                                <option value="">Select Account</option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ old('savings_control_account_id', $savingsProduct->savings_control_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('savings_control_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                Usually a member savings liability account.
                            </small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Interest Expense Account <span class="text-danger">*</span>
                            </label>

                            <select name="interest_expense_account_id"
                                    class="form-select @error('interest_expense_account_id') is-invalid @enderror"
                                    required>

                                <option value="">Select Account</option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ old('interest_expense_account_id', $savingsProduct->interest_expense_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('interest_expense_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                Used when interest is credited to member savings.
                            </small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Fee Income Account <span class="text-danger">*</span>
                            </label>

                            <select name="fee_income_account_id"
                                    class="form-select @error('fee_income_account_id') is-invalid @enderror"
                                    required>

                                <option value="">Select Account</option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ old('fee_income_account_id', $savingsProduct->fee_income_account_id ?? '') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('fee_income_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="text-muted">
                                Used for withdrawal charges and other savings-related fees.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Continue with Interest Configuration & Fixed Deposit Settings -->
            <!-- Interest Configuration -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Interest Configuration</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Interest Rate (%)
                            </label>

                            <div class="input-group">
                                <input type="number"
                                       name="interest_rate"
                                       id="interest_rate"
                                       step="0.0001"
                                       min="0"
                                       class="form-control text-end @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate', $savingsProduct->interest_rate ?? 0) }}">

                                <span class="input-group-text">%</span>

                                @error('interest_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Example: 8.5000 represents 8.5%.
                            </small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Interest Frequency
                            </label>

                            <select name="interest_frequency"
                                    id="interest_frequency"
                                    class="form-select @error('interest_frequency') is-invalid @enderror">

                                <option value="annual"
                                    {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === 'annual' ? 'selected' : '' }}>
                                    Annual
                                </option>

                                <option value="monthly"
                                    {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === 'monthly' ? 'selected' : '' }}>
                                    Monthly
                                </option>

                                <option value="quarterly"
                                    {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === 'quarterly' ? 'selected' : '' }}>
                                    Quarterly
                                </option>

                                <option value="semi_annual"
                                    {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === 'semi_annual' ? 'selected' : '' }}>
                                    Semi-Annual
                                </option>

                                <option value="at_maturity"
                                    {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === 'at_maturity' ? 'selected' : '' }}>
                                    At Maturity
                                </option>
                            </select>

                            @error('interest_frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Interest Calculation Method
                            </label>

                            <select name="interest_calculation_method"
                                    id="interest_calculation_method"
                                    class="form-select @error('interest_calculation_method') is-invalid @enderror">

                                <option value="simple"
                                    {{ old('interest_calculation_method', $savingsProduct->interest_calculation_method ?? 'simple') === 'simple' ? 'selected' : '' }}>
                                    Simple Interest
                                </option>

                                <option value="compound"
                                    {{ old('interest_calculation_method', $savingsProduct->interest_calculation_method ?? 'simple') === 'compound' ? 'selected' : '' }}>
                                    Compound Interest
                                </option>
                            </select>

                            @error('interest_calculation_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        Interest configuration applies mainly to interest-bearing savings and fixed deposit products.
                        Products with no interest should use a rate of 0%.
                    </div>
                </div>
            </div>

            <!-- Fixed Deposit Settings -->
            <div class="card shadow-sm mb-4" id="fixedDepositSection">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Fixed Deposit Settings</h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        These settings apply when the product type is Fixed Deposit.
                    </div>

                    <!-- Term Configuration -->
                    <h6 class="fw-bold mb-3">Deposit Term</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Minimum Term
                            </label>

                            <div class="input-group">
                                <input type="number"
                                       name="minimum_term_months"
                                       id="minimum_term_months"
                                       min="0"
                                       class="form-control @error('minimum_term_months') is-invalid @enderror"
                                       value="{{ old('minimum_term_months', $savingsProduct->minimum_term_months ?? 0) }}">

                                <span class="input-group-text">Months</span>

                                @error('minimum_term_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Maximum Term
                            </label>

                            <div class="input-group">
                                <input type="number"
                                       name="maximum_term_months"
                                       id="maximum_term_months"
                                       min="0"
                                       class="form-control @error('maximum_term_months') is-invalid @enderror"
                                       value="{{ old('maximum_term_months', $savingsProduct->maximum_term_months ?? '') }}">

                                <span class="input-group-text">Months</span>

                                @error('maximum_term_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Leave blank if the product has no maximum term.
                            </small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Maximum Balance
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input type="number"
                                       name="maximum_balance"
                                       id="maximum_balance"
                                       step="0.01"
                                       min="0"
                                       class="form-control text-end @error('maximum_balance') is-invalid @enderror"
                                       value="{{ old('maximum_balance', $savingsProduct->maximum_balance ?? '') }}">

                                @error('maximum_balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Leave blank if there is no maximum deposit limit.
                            </small>
                        </div>
                    </div>

                    <hr>

                    <!-- Premature Withdrawal -->
                    <h6 class="fw-bold mb-3">Premature Withdrawal</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Allow Premature Withdrawal
                            </label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="allows_premature_withdrawal"
                                       id="allows_premature_withdrawal"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('allows_premature_withdrawal', $savingsProduct->allows_premature_withdrawal ?? false) ? 'checked' : '' }}>

                                <label class="form-check-label"
                                       for="allows_premature_withdrawal">
                                    Yes
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3" id="prematurePenaltyContainer">
                            <label class="form-label">
                                Premature Withdrawal Penalty (%)
                            </label>

                            <div class="input-group">
                                <input type="number"
                                       name="premature_withdrawal_penalty_percentage"
                                       id="premature_withdrawal_penalty_percentage"
                                       step="0.0001"
                                       min="0"
                                       max="100"
                                       class="form-control text-end @error('premature_withdrawal_penalty_percentage') is-invalid @enderror"
                                       value="{{ old('premature_withdrawal_penalty_percentage', $savingsProduct->premature_withdrawal_penalty_percentage ?? 0) }}">

                                <span class="input-group-text">%</span>

                                @error('premature_withdrawal_penalty_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Allow Partial Withdrawals
                            </label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="allows_partial_withdrawals"
                                       id="allows_partial_withdrawals"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('allows_partial_withdrawals', $savingsProduct->allows_partial_withdrawals ?? false) ? 'checked' : '' }}>

                                <label class="form-check-label"
                                       for="allows_partial_withdrawals">
                                    Yes
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Rollover Configuration -->
                    <h6 class="fw-bold mb-3">Maturity & Rollover</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Automatic Rollover
                            </label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="auto_rollover"
                                       id="auto_rollover"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('auto_rollover', $savingsProduct->auto_rollover ?? false) ? 'checked' : '' }}>

                                <label class="form-check-label"
                                       for="auto_rollover">
                                    Yes
                                </label>
                            </div>

                            <small class="text-muted">
                                Automatically renew the deposit after maturity.
                            </small>
                        </div>

                        <div class="col-md-4 mb-3" id="rolloverOptionContainer">
                            <label class="form-label">
                                Rollover Option
                            </label>

                            <select name="rollover_option"
                                    id="rollover_option"
                                    class="form-select @error('rollover_option') is-invalid @enderror">

                                <option value="">
                                    Select Option
                                </option>

                                <option value="principal_only"
                                    {{ old('rollover_option', $savingsProduct->rollover_option ?? '') === 'principal_only' ? 'selected' : '' }}>
                                    Principal Only
                                </option>

                                <option value="principal_and_interest"
                                    {{ old('rollover_option', $savingsProduct->rollover_option ?? '') === 'principal_and_interest' ? 'selected' : '' }}>
                                    Principal + Interest
                                </option>
                            </select>

                            @error('rollover_option')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-warning mb-0">
                        For a fixed deposit, the actual interest rate, start date, maturity date, principal, and rollover
                        instruction should also be copied to the member's fixed-deposit contract when the account is opened.
                    </div>
                </div>
            </div>

            <!-- Continue with Savings Rules + Withdrawal & Loan Security -->
            <!-- Savings Rules -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Savings Rules</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Balance</label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input type="number"
                                       name="minimum_balance"
                                       step="0.01"
                                       min="0"
                                       class="form-control text-end @error('minimum_balance') is-invalid @enderror"
                                       value="{{ old('minimum_balance', $savingsProduct->minimum_balance ?? 0) }}">

                                @error('minimum_balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Monthly Contribution</label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input type="number"
                                       name="minimum_monthly_contribution"
                                       step="0.01"
                                       min="0"
                                       class="form-control text-end @error('minimum_monthly_contribution') is-invalid @enderror"
                                       value="{{ old('minimum_monthly_contribution', $savingsProduct->minimum_monthly_contribution ?? 0) }}">

                                @error('minimum_monthly_contribution')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Mainly applicable to compulsory or recurring savings products.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Rules -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Withdrawal Rules</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Allows Withdrawals</label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="allows_withdrawals"
                                       id="allows_withdrawals"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('allows_withdrawals', $savingsProduct->allows_withdrawals ?? true) ? 'checked' : '' }}>

                                <label class="form-check-label" for="allows_withdrawals">
                                    Yes
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3" id="withdrawalNoticeContainer">
                            <label class="form-label">Withdrawal Notice Period</label>

                            <div class="input-group">
                                <input type="number"
                                       name="withdrawal_notice_days"
                                       id="withdrawal_notice_days"
                                       min="0"
                                       class="form-control @error('withdrawal_notice_days') is-invalid @enderror"
                                       value="{{ old('withdrawal_notice_days', $savingsProduct->withdrawal_notice_days ?? 0) }}">

                                <span class="input-group-text">Days</span>

                                @error('withdrawal_notice_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Use 0 for immediate withdrawal.
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0">
                        Fixed deposits may use the premature withdrawal rules configured separately even when normal withdrawals are disabled.
                    </div>
                </div>
            </div>

            <!-- Loan Security -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Loan Security Eligibility</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Can Secure Loan</label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="can_secure_loan"
                                       id="can_secure_loan"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('can_secure_loan', $savingsProduct->can_secure_loan ?? false) ? 'checked' : '' }}>

                                <label class="form-check-label" for="can_secure_loan">
                                    Yes
                                </label>
                            </div>

                            <small class="text-muted">
                                Allows balances held under this savings product to be pledged as loan security.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Product Status</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Active Product</label>

                            <div class="form-check form-switch mt-2">
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       class="form-check-input"
                                       value="1"
                                       {{ old('is_active', $savingsProduct->is_active ?? true) ? 'checked' : '' }}>

                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Status</label>

                            <input type="text"
                                   id="productStatus"
                                   class="form-control bg-light"
                                   value="{{ old('is_active', $savingsProduct->is_active ?? true) ? 'Active' : 'Inactive' }}"
                                   readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Continue with Form Actions + jQuery -->
            <!-- Form Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Fields marked with <span class="text-danger">*</span> are required.
                </div>

                <div>
                    <a href="{{ route('savings_products.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Save Savings Product
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
    function toggleFixedDepositSection() {
        const isFixedDeposit = $('#product_type').val() === 'fixed_deposit';
        $('#fixedDepositSection').toggle(isFixedDeposit);

        if (!isFixedDeposit) {
            $('#minimum_term_months').val(0);
            $('#maximum_term_months').val('');
            $('#allows_premature_withdrawal').prop('checked', false);
            $('#premature_withdrawal_penalty_percentage').val(0);
            $('#auto_rollover').prop('checked', false);
            $('#rollover_option').val('');
            $('#allows_partial_withdrawals').prop('checked', false);
        }

        togglePrematureWithdrawal();
        toggleRollover();
    }

    function togglePrematureWithdrawal() {
        const allowed = $('#allows_premature_withdrawal').is(':checked');
        $('#prematurePenaltyContainer').toggle(allowed);

        if (!allowed) {
            $('#premature_withdrawal_penalty_percentage').val(0);
        }
    }

    function toggleRollover() {
        const enabled = $('#auto_rollover').is(':checked');
        $('#rolloverOptionContainer').toggle(enabled);

        if (!enabled) {
            $('#rollover_option').val('');
        }
    }

    function toggleWithdrawalRules() {
        const allowed = $('#allows_withdrawals').is(':checked');
        $('#withdrawalNoticeContainer').toggle(allowed);

        if (!allowed) {
            $('#withdrawal_notice_days').val(0);
        }
    }

    function updateProductStatus() {
        $('#productStatus').val(
            $('#is_active').is(':checked') ? 'Active' : 'Inactive'
        );
    }

    $('#product_type').on('change', toggleFixedDepositSection);
    $('#allows_premature_withdrawal').on('change', togglePrematureWithdrawal);
    $('#auto_rollover').on('change', toggleRollover);
    $('#allows_withdrawals').on('change', toggleWithdrawalRules);
    $('#is_active').on('change', updateProductStatus);

    $('#savingsProductForm').on('submit', function () {
        const minBalance = parseFloat($('input[name="minimum_balance"]').val()) || 0;
        const maxBalance = parseFloat($('#maximum_balance').val()) || 0;
        const minTerm = parseInt($('#minimum_term_months').val()) || 0;
        const maxTerm = parseInt($('#maximum_term_months').val()) || 0;
        const interestRate = parseFloat($('#interest_rate').val()) || 0;
        const penaltyRate = parseFloat($('#premature_withdrawal_penalty_percentage').val()) || 0;

        if (minBalance < 0 || maxBalance < 0) {
            alert('Savings balances cannot be negative.');
            return false;
        }

        if (maxBalance > 0 && maxBalance < minBalance) {
            alert('Maximum balance cannot be less than minimum balance.');
            return false;
        }

        if ($('#product_type').val() === 'fixed_deposit') {
            if (minTerm <= 0) {
                alert('Fixed deposit products must have a minimum term greater than zero.');
                return false;
            }

            if (maxTerm > 0 && maxTerm < minTerm) {
                alert('Maximum term cannot be less than minimum term.');
                return false;
            }

            if (interestRate < 0) {
                alert('Interest rate cannot be negative.');
                return false;
            }

            if ($('#allows_premature_withdrawal').is(':checked') && (penaltyRate < 0 || penaltyRate > 100)) {
                alert('Premature withdrawal penalty must be between 0% and 100%.');
                return false;
            }

            if ($('#auto_rollover').is(':checked') && !$('#rollover_option').val()) {
                alert('Please select a rollover option.');
                return false;
            }
        }

        return true;
    });

    toggleFixedDepositSection();
    togglePrematureWithdrawal();
    toggleRollover();
    toggleWithdrawalRules();
    updateProductStatus();
});    
</script>
@endsection


