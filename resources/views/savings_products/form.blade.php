{{-- PRODUCT INFORMATION --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Product Information</h5>
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Product Code <span class="text-danger">*</span>
                </label>

                <input type="text"
                       name="code"
                       id="code"
                       maxlength="50"
                       required
                       autocomplete="off"
                       class="form-control text-uppercase @error('code') is-invalid @enderror"
                       value="{{ old('code', $savingsProduct->code ?? '') }}">

                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <small class="text-muted">
                    Example: SAV, COMP, FD01
                </small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Product Name <span class="text-danger">*</span>
                </label>

                <input type="text"
                       name="name"
                       id="name"
                       maxlength="255"
                       required
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $savingsProduct->name ?? '') }}">

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
                        required
                        class="form-select @error('product_type') is-invalid @enderror">
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
    </div>
</div>

{{-- GL MAPPING --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">General Ledger Mapping</h5>
    </div>

    <div class="card-body">
        <div class="alert alert-info">
            Select the GL accounts that will receive the corresponding savings accounting entries.
        </div>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Savings Control Account <span class="text-danger">*</span>
                </label>

                <select name="savings_control_account_id"
                        id="savings_control_account_id"
                        required
                        class="form-select @error('savings_control_account_id') is-invalid @enderror">
                    <option value="0">Select Account</option>

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
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Interest Expense Account <span class="text-danger">*</span>
                </label>

                <select name="interest_expense_account_id"
                        id="interest_expense_account_id"
                        required
                        class="form-select @error('interest_expense_account_id') is-invalid @enderror">
                    <option value="0">Select Account</option>

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
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Fee Income Account <span class="text-danger">*</span>
                </label>

                <select name="fee_income_account_id"
                        id="fee_income_account_id"
                        required
                        class="form-select @error('fee_income_account_id') is-invalid @enderror">
                    <option value="0">Select Account</option>

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
            </div>

        </div>
    </div>
</div>

{{-- INTEREST --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Interest Configuration</h5>
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Interest Rate <span class="text-danger">*</span>
                </label>

                <div class="input-group">
                    <input type="number"
                           name="interest_rate"
                           id="interest_rate"
                           min="0"
                           max="100"
                           step="0.0001"
                           required
                           class="form-control text-end @error('interest_rate') is-invalid @enderror"
                           value="{{ old('interest_rate', $savingsProduct->interest_rate ?? 0) }}">
                    <span class="input-group-text">%</span>

                    @error('interest_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Interest Frequency <span class="text-danger">*</span>
                </label>

                <select name="interest_frequency"
                        id="interest_frequency"
                        required
                        class="form-select @error('interest_frequency') is-invalid @enderror">
                    @foreach([
                        'annual' => 'Annual',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annual' => 'Semi Annual',
                        'at_maturity' => 'At Maturity',
                    ] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('interest_frequency', $savingsProduct->interest_frequency ?? 'at_maturity') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('interest_frequency')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Calculation Method <span class="text-danger">*</span>
                </label>

                <select name="interest_calculation_method"
                        id="interest_calculation_method"
                        required
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
            Products that do not earn interest should use an interest rate of <strong>0%</strong>.
        </div>
    </div>
</div>

{{-- FIXED DEPOSIT --}}
<div class="card shadow-sm border-0 mb-4" id="fixedDepositSection">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Fixed Deposit Settings</h5>
    </div>

    <div class="card-body">

        <div class="alert alert-info">
            These settings apply to fixed-deposit products.
        </div>

        <h6 class="fw-bold mb-3">Deposit Term & Limits</h6>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">Minimum Term</label>

                <div class="input-group">
                    <input type="number"
                           name="minimum_term_months"
                           id="minimum_term_months"
                           min="0"
                           step="1"
                           class="form-control @error('minimum_term_months') is-invalid @enderror"
                           value="{{ old('minimum_term_months', $savingsProduct->minimum_term_months ?? 0) }}">
                    <span class="input-group-text">Months</span>
                </div>

                @error('minimum_term_months')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Maximum Term</label>

                <div class="input-group">
                    <input type="number"
                           name="maximum_term_months"
                           id="maximum_term_months"
                           min="0"
                           step="1"
                           class="form-control @error('maximum_term_months') is-invalid @enderror"
                           value="{{ old('maximum_term_months', $savingsProduct->maximum_term_months ?? '') }}">
                    <span class="input-group-text">Months</span>
                </div>

                @error('maximum_term_months')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <small class="text-muted">Leave blank for no maximum.</small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Maximum Balance</label>

                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number"
                           name="maximum_balance"
                           id="maximum_balance"
                           min="0"
                           step="0.01"
                           class="form-control text-end @error('maximum_balance') is-invalid @enderror"
                           value="{{ old('maximum_balance', $savingsProduct->maximum_balance ?? '') }}">
                </div>

                @error('maximum_balance')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <small class="text-muted">Leave blank for no maximum.</small>
            </div>

        </div>

        <hr>

        <h6 class="fw-bold mb-3">Withdrawal & Maturity</h6>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">Allow Premature Withdrawal</label>

                <input type="hidden" name="allows_premature_withdrawal" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="allows_premature_withdrawal"
                           id="allows_premature_withdrawal"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('allows_premature_withdrawal') ? 'checked' : '' }}>
                    <label class="form-check-label" for="allows_premature_withdrawal">
                        Yes
                    </label>
                </div>
            </div>

            <div class="col-md-4 mb-3" id="prematurePenaltyContainer">
                <label class="form-label">Premature Withdrawal Penalty</label>

                <div class="input-group">
                    <input type="number"
                           name="premature_withdrawal_penalty_percentage"
                           id="premature_withdrawal_penalty_percentage"
                           min="0"
                           max="100"
                           step="0.0001"
                           class="form-control text-end @error('premature_withdrawal_penalty_percentage') is-invalid @enderror"
                           value="{{ old('premature_withdrawal_penalty_percentage', $savingsProduct->premature_withdrawal_penalty_percentage ?? 0) }}">
                    <span class="input-group-text">%</span>
                </div>

                @error('premature_withdrawal_penalty_percentage')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Allow Partial Withdrawals</label>

                <input type="hidden" name="allows_partial_withdrawals" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="allows_partial_withdrawals"
                           id="allows_partial_withdrawals"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('allows_partial_withdrawals') ? 'checked' : '' }}>
                    <label class="form-check-label" for="allows_partial_withdrawals">
                        Yes
                    </label>
                </div>
            </div>

        </div>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">Automatic Rollover</label>

                <input type="hidden" name="auto_rollover" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="auto_rollover"
                           id="auto_rollover"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('auto_rollover') ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_rollover">
                        Yes
                    </label>
                </div>
            </div>

            <div class="col-md-4 mb-3" id="rolloverOptionContainer">
                <label class="form-label">Rollover Option</label>

                <select name="rollover_option"
                        id="rollover_option"
                        class="form-select @error('rollover_option') is-invalid @enderror">
                    <option value="">Select Option</option>
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
    </div>
</div>

{{-- SAVINGS RULES --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">Savings Rules</h5>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Minimum Balance <span class="text-danger">*</span>
                </label>

                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number"
                           name="minimum_balance"
                           id="minimum_balance"
                           min="0"
                           step="0.01"
                           required
                           class="form-control text-end @error('minimum_balance') is-invalid @enderror"
                           value="{{ old('minimum_balance', $savingsProduct->minimum_balance ?? 0) }}">
                </div>

                @error('minimum_balance')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Minimum Monthly Contribution <span class="text-danger">*</span>
                </label>

                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number"
                           name="minimum_monthly_contribution"
                           id="minimum_monthly_contribution"
                           min="0"
                           step="0.01"
                           required
                           class="form-control text-end @error('minimum_monthly_contribution') is-invalid @enderror"
                           value="{{ old('minimum_monthly_contribution', $savingsProduct->minimum_monthly_contribution ?? 0) }}">
                </div>

                @error('minimum_monthly_contribution')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Can Secure Loan</label>

                <input type="hidden" name="can_secure_loan" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="can_secure_loan"
                           id="can_secure_loan"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('can_secure_loan') ? 'checked' : '' }}>
                    <label class="form-check-label" for="can_secure_loan">
                        Yes
                    </label>
                </div>

                <small class="text-muted">
                    Allows savings balances to be considered for loan security.
                </small>
            </div>

        </div>

        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="form-label">Allows Withdrawals</label>

                <input type="hidden" name="allows_withdrawals" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="allows_withdrawals"
                           id="allows_withdrawals"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('allows_withdrawals', true) ? 'checked' : '' }}>
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
                           step="1"
                           class="form-control @error('withdrawal_notice_days') is-invalid @enderror"
                           value="{{ old('withdrawal_notice_days', $savingsProduct->withdrawal_notice_days ?? 0) }}">
                    <span class="input-group-text">Days</span>
                </div>

                @error('withdrawal_notice_days')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

        </div>

    </div>
</div>

{{-- PRODUCT STATUS --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Product Status</h5>
    </div>

    <div class="card-body">
        <div class="row align-items-end">

            <div class="col-md-4 mb-3">
                <label class="form-label">Active Product</label>

                <input type="hidden" name="is_active" value="0">

                <div class="form-check form-switch mt-2">
                    <input type="checkbox"
                           name="is_active"
                           id="is_active"
                           class="form-check-input"
                           value="1"
                           {{ $booleanValue('is_active', true) ? 'checked' : '' }}>

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

{{-- ACTIONS --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted small">
            Fields marked with <span class="text-danger">*</span> are required.
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('savings_products.index') }}" class="btn btn-light">
                Cancel
            </a>

            <button type="submit" id="saveButton" class="btn btn-primary">
                <i class="fa fa-save me-1"></i>
                {{ $editing ? 'Update Savings Product' : 'Save Savings Product' }}
            </button>
        </div>
    </div>
</div>
