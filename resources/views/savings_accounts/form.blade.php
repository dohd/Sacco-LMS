<!-- ============================= -->
<!-- ACCOUNT DETAILS -->
<!-- ============================= -->

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            Savings Account
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">
                    Opened Date
                    <span class="text-danger">*</span>
                </label>
                <input type="date"
                       name="opened_date"
                       class="form-control"
                       value="{{ old('opened_date', @$savingsAccount->opened_date ?: now()->format('Y-m-d')) }}"
                       required>
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">
                    Member
                    <span class="text-danger">*</span>
                </label>
                <select name="member_id"
                        id="member_id"
                        class="form-select"
                        required>
                    <option value="">
                        Select Member
                    </option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}"
                            {{ old('member_id', $savingsAccount->member_id ?? '') == $member->id ? 'selected' : '' }}>
                            {{ $member->membership_number }} -  {{ $member->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Savings Product
                    <span class="text-danger">*</span>
                </label>
                <select name="savings_product_id"
                        id="savings_product_id"
                        class="form-select"
                        required>
                    <option value="">
                        Select Product
                    </option>
                    @foreach($savingsProducts as $product)
                        <option value="{{ $product->id }}"
                            data-product-type="{{ $product->product_type }}"
                            data-minimum-term="{{ $product->minimum_term_months }}"
                            data-maximum-term="{{ $product->maximum_term_months }}"
                            data-minimum-balance="{{ $product->minimum_balance }}"
                            data-maximum-balance="{{ $product->maximum_balance }}"
                            {{ old('savings_product_id', $savingsAccount->savings_product_id ?? '') == $product->id ? 'selected' : '' }}>
                            {{ $product->code }}
                            -
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @empty($savingsAccount)
            <div class="row d-none" id="fixedDepositOpeningFields">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Opening Deposit (KES) <span class="text-danger">*</span></label>
                    <input type="number" min="0.01" step="0.01" name="initial_deposit_amount" id="initial_deposit_amount"
                           value="{{ old('initial_deposit_amount') }}" class="form-control @error('initial_deposit_amount') is-invalid @enderror">
                    @error('initial_deposit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" min="1" step="1" name="fixed_deposit_term_months" id="fixed_deposit_term_months"
                               value="{{ old('fixed_deposit_term_months') }}" class="form-control @error('fixed_deposit_term_months') is-invalid @enderror">
                        <span class="input-group-text">months</span>
                    </div>
                    @error('fixed_deposit_term_months')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Deposit Cash/Bank Account <span class="text-danger">*</span></label>
                    <select name="deposit_account_id" id="fixed_deposit_deposit_account_id" class="form-select">
                        <option value="">Select receiving account</option>
                        @foreach($depositAccounts as $account)
                            <option value="{{ $account->id }}" {{ old('deposit_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->code }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('deposit_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0">The fixed deposit is funded immediately. Its maturity date is calculated from the opened date and selected term.</div>
                </div>
            </div>
        @endempty

        @isset($savingsAccount)
        	<div class="row">
        		<div class="col-md-3 mb-3">
                    <label class="form-label">
                        Status
                    </label>
                    <p class="form-control-plaintext">Status changes use the actions on the account detail page.</p>
                </div>
        	</div>
        @endisset

    </div>
</div>

<!-- ============================= -->
<!-- ACCOUNT BALANCES -->
<!-- ============================= -->

@isset($savingsAccount)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            Account Balances
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Ledger Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->ledger_balance,2) }}"
                           readonly>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Held Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->held_balance,2) }}"
                           readonly>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Available Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->available_balance,2) }}"
                           readonly>
                </div>
            </div>
        </div>
    </div>
</div>
@endisset

<!-- ============================= -->
<!-- ACTIONS -->
<!-- ============================= -->

<div class="card shadow-sm">
    <div class="card-body d-flex justify-content-between">
        <a href="{{ route('savings_accounts.index') }}"
           class="btn btn-light">
            Cancel
        </a>
        <button type="submit"
                class="btn btn-primary">
            Save Savings Account
        </button>
    </div>
</div>
