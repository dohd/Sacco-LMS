<div class="row g-3">
    {{-- ========================================================= --}}
    {{-- SAVINGS ACCOUNT --}}
    {{-- ========================================================= --}}

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-wallet me-1"></i>
                    Savings Account
                </strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="savings_account_id" class="form-label">
                            Savings Account <span class="text-danger">*</span>
                        </label>
                        <select name="savings_account_id"
                                id="savings_account_id"
                                class="form-select @error('savings_account_id') is-invalid @enderror"
                                required>
                            <option value="">Select Savings Account</option>
                            @foreach($savingsAccounts as $account)
                                <option value="{{ $account->id }}"
                                        data-balance="{{ $account->available_balance }}"
                                        data-product="{{ $account->savingsProduct->name ?? '' }}"
                                        {{ old('savings_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_number }}
                                    -
                                    {{ $account->member->member_number ?? '' }}
                                    -
                                    {{ $account->member->first_name ?? '' }}
                                    {{ $account->member->last_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('savings_account_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    {{-- Account Balance --}}
                    <div class="col-md-3">
                        <label class="form-label">
                            Available Balance
                        </label>
                        <div class="form-control bg-light"
                             id="available_balance">
                            KES 0.00
                        </div>
                    </div>
                    {{-- Savings Product --}}
                    <div class="col-md-3">
                        <label class="form-label">
                            Savings Product
                        </label>
                        <div class="form-control bg-light"
                             id="savings_product">
                            —
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- WITHDRAWAL DETAILS --}}
    {{-- ========================================================= --}}

    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-money-bill-wave me-1"></i>
                    Withdrawal Details
                </strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="amount" class="form-label">
                            Withdrawal Amount <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                KES
                            </span>
                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}"
                                   min="0.01"
                                   step="0.01"
                                   required>
                        </div>
                        @error('amount')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                        <div id="balanceWarning"
                             class="text-danger small mt-1 d-none">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="requested_date" class="form-label">
                            Requested Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               name="requested_date"
                               id="requested_date"
                               class="form-control @error('requested_date') is-invalid @enderror"
                               value="{{ old('requested_date', date('Y-m-d')) }}"
                               required>
                        @error('requested_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- REQUEST SUMMARY --}}
    {{-- ========================================================= --}}

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-calculator me-1"></i>
                    Withdrawal Summary
                </strong>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                        Available Balance
                    </span>
                    <strong id="summaryBalance">
                        KES 0.00
                    </strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">
                        Withdrawal Amount
                    </span>
                    <strong id="summaryAmount">
                        KES 0.00
                    </strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">
                        Balance After Withdrawal
                    </span>
                    <strong id="summaryRemaining">
                        KES 0.00
                    </strong>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- PAYMENT DETAILS --}}
    {{-- ========================================================= --}}

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-credit-card me-1"></i>
                    Payment Details
                </strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="payment_method" class="form-label">
                            Payment Method <span class="text-danger">*</span>
                        </label>
                        <select name="payment_method"
                                id="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror"
                                required>
                            <option value="">Select Payment Method</option>
                            <option value="cash"
                                {{ old('payment_method') == 'cash' ? 'selected' : '' }}>
                                Cash
                            </option>
                            <option value="mobile_money"
                                {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>
                                Mobile Money
                            </option>
                            <option value="bank_transfer"
                                {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>
                                Bank Transfer
                            </option>
                            <option value="cheque"
                                {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>
                                Cheque
                            </option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    {{-- Mobile Money --}}
                    <div class="col-md-4 payment-field"
                         id="mobileMoneyField">
                        <label for="mobile_money_number" class="form-label">
                            Mobile Money Number
                        </label>
                        <input type="text"
                               name="mobile_money_number"
                               id="mobile_money_number"
                               class="form-control"
                               value="{{ old('mobile_money_number') }}"
                               placeholder="e.g. 0712345678">
                    </div>
                    {{-- Bank Account --}}
                    <div class="col-md-4 payment-field"
                         id="bankAccountField">
                        <label for="bank_account_number" class="form-label">
                            Bank Account Number
                        </label>
                        <input type="text"
                               name="bank_account_number"
                               id="bank_account_number"
                               class="form-control"
                               value="{{ old('bank_account_number') }}">
                    </div>
                    {{-- Bank Name --}}
                    <div class="col-md-4 payment-field"
                         id="bankNameField">
                        <label for="bank_name" class="form-label">
                            Bank Name
                        </label>
                        <input type="text"
                               name="bank_name"
                               id="bank_name"
                               class="form-control"
                               value="{{ old('bank_name') }}">
                    </div>
                    {{-- Cheque --}}
                    <div class="col-md-4 payment-field"
                         id="chequeField">
                        <label for="cheque_number" class="form-label">
                            Cheque Number
                        </label>
                        <input type="text"
                               name="cheque_number"
                               id="cheque_number"
                               class="form-control"
                               value="{{ old('cheque_number') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- REASON --}}
    {{-- ========================================================= --}}

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-comment-alt me-1"></i>
                    Reason / Additional Information
                </strong>
            </div>
            <div class="card-body">
                <label for="reason" class="form-label">
                    Reason for Withdrawal
                </label>
                <textarea name="reason"
                          id="reason"
                          rows="4"
                          class="form-control"
                          placeholder="Enter the reason for the withdrawal...">{{ old('reason') }}</textarea>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- DECLARATION --}}
    {{-- ========================================================= --}}

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="form-check">
                    <input type="checkbox"
                           class="form-check-input"
                           id="confirm_information"
                           required>
                    <label class="form-check-label"
                           for="confirm_information">
                        I confirm that the withdrawal information provided
                        is accurate and that the withdrawal is subject to
                        the applicable approval process.
                    </label>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- ACTIONS --}}
    {{-- ========================================================= --}}

    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('savings_withdrawals.index') }}"
               class="btn btn-secondary">
                Cancel
            </a>
            <button type="submit"
                    class="btn btn-primary"
                    id="submitBtn">
                <i class="fa fa-paper-plane me-1"></i>
                Submit Withdrawal Request
            </button>
        </div>
    </div>
</div>
