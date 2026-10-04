<div class="row g-4">

    {{-- Main --}}
    <div class="col-lg-8">

        {{-- Account & Transaction --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-exchange-alt text-primary me-2"></i>
                    Transaction Details
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- Share Account --}}
                    <div class="col-md-6">
                        <label for="share_account_id" class="form-label">
                            Share Account <span class="text-danger">*</span>
                        </label>

                        <select name="share_account_id"
                                id="share_account_id"
                                class="form-select @error('share_account_id') is-invalid @enderror"
                                required>

                            <option value="">Select share account</option>

                            @foreach($shareAccounts as $account)
                                <option value="{{ $account->id }}"
                                        data-member="{{ optional($account->member)->full_name }}"
                                        data-account-number="{{ $account->account_number }}"
                                        data-unit-value="{{ optional($account->shareProduct)->unit_value }}"
                                        data-running-units="{{ $account->total_units }}"
                                        data-running-balance="{{ $account->share_balance }}"
                                        data-status="{{ $account->status }}"
                                        {{ old('share_account_id') == $account->id ? 'selected' : '' }}
                                    >
                                        {{ $account->account_number }} - {{ @$account->member->membership_number }} {{ @$account->member->full_name }}                                
                                </option>
                            @endforeach

                        </select>

                        @error('share_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Transaction Type --}}
                    <div class="col-md-6">
                        <label for="transaction_type" class="form-label">
                            Transaction Type <span class="text-danger">*</span>
                        </label>

                        <select name="transaction_type"
                                id="transaction_type"
                                class="form-select @error('transaction_type') is-invalid @enderror"
                                required>

                            <option value="">Select transaction type</option>
                            <option value="purchase" {{ old('transaction_type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                            <option value="transfer_in" {{ old('transaction_type') === 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
                            <option value="transfer_out" {{ old('transaction_type') === 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                            <option value="redemption" {{ old('transaction_type') === 'redemption' ? 'selected' : '' }}>Redemption</option>
                            <option value="bonus_issue" {{ old('transaction_type') === 'bonus_issue' ? 'selected' : '' }}>Bonus Issue</option>
                            <option value="adjustment" {{ old('transaction_type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        </select>

                        @error('transaction_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

            </div>
        </div>


        {{-- Transaction Amount --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-calculator text-primary me-2"></i>
                    Share Transaction Value
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- Direction --}}
                    <div class="col-md-4" id="directionWrapper">
                        <label for="direction" class="form-label">
                            Direction <span class="text-danger">*</span>
                        </label>

                        <select name="direction"
                                id="direction"
                                class="form-select @error('direction') is-invalid @enderror">

                            <option value="">Select direction</option>
                            <option value="credit" {{ old('direction') === 'credit' ? 'selected' : '' }}>
                                Credit
                            </option>
                            <option value="debit" {{ old('direction') === 'debit' ? 'selected' : '' }}>
                                Debit
                            </option>

                        </select>

                        @error('direction')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Units --}}
                    <div class="col-md-4">
                        <label for="units" class="form-label">
                            Units <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               name="units"
                               id="units"
                               min="1"
                               step="1"
                               value="{{ old('units') }}"
                               class="form-control @error('units') is-invalid @enderror"
                               required>

                        @error('units')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Unit Value --}}
                    <div class="col-md-4">
                        <label for="unit_value" class="form-label">
                            Unit Value <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">KES</span>

                            <input type="number"
                               name="unit_value"
                               id="unit_value"
                               step="0.01"
                               min="0"
                               value="{{ old('unit_value') }}"
                               class="form-control @error('unit_value') is-invalid @enderror"
                               readonly
                               required
                            >
                        </div>

                        @error('unit_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Amount --}}
                    <div class="col-md-6">
                        <label for="amount" class="form-label">
                            Transaction Amount <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">KES</span>

                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   step="0.01"
                                   min="0"
                                   value="{{ old('amount') }}"
                                   class="form-control fw-bold @error('amount') is-invalid @enderror"
                                   readonly
                                   required>
                        </div>

                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            Calculated from units × unit value.
                        </div>
                    </div>

                    {{-- Transaction Date --}}
                    <div class="col-md-3">
                        <label for="transaction_date" class="form-label">
                            Transaction Date <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="transaction_date"
                               id="transaction_date"
                               value="{{ old('transaction_date', date('Y-m-d')) }}"
                               class="form-control @error('transaction_date') is-invalid @enderror"
                               required>

                        @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Value Date --}}
                    <div class="col-md-3">
                        <label for="value_date" class="form-label">
                            Value Date <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="value_date"
                               id="value_date"
                               value="{{ old('value_date', date('Y-m-d')) }}"
                               class="form-control @error('value_date') is-invalid @enderror"
                               required>

                        @error('value_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

            </div>
        </div>


        {{-- Payment Information --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-money-bill-wave text-primary me-2"></i>
                    Payment Information
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="payment_method" class="form-label">
                            Payment Method
                        </label>

                        <select name="payment_method"
                                id="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror"
                            >
                            <option value="">Select payment method</option>                            
                            <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="mobile_money {{ old('payment_method', 'mobile_money') === 'mobile_money' ? 'selected' : '' }}">Mobile Money</option>
                            <option value="bank_transfer" {{ old('payment_method', 'bank_transfer') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cheque" {{ old('payment_method', 'cheque') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="check_off" {{ old('payment_method', 'check_off') === 'check_off' ? 'selected' : '' }}>Check Off</option>
                            <option value="internal_transfer" {{ old('payment_method', 'internal_transfer') === 'internal_transfer' ? 'selected' : '' }}>Internal Transfer</option>
                            <option value="system" {{ old('payment_method', 'system') === 'system' ? 'selected' : '' }}>System</option>
                        </select>

                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="payment_reference" class="form-label">
                            Payment Reference
                        </label>

                        <input type="text"
                               name="payment_reference"
                               id="payment_reference"
                               value="{{ old('payment_reference') }}"
                               class="form-control @error('payment_reference') is-invalid @enderror"
                               maxlength="255">

                        @error('payment_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="receipt_number" class="form-label">
                            Receipt Number
                        </label>

                        <input type="text"
                               name="receipt_number"
                               id="receipt_number"
                               value="{{ old('receipt_number') }}"
                               class="form-control @error('receipt_number') is-invalid @enderror"
                               maxlength="255">

                        @error('receipt_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

            </div>
        </div>


        {{-- Description --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-comment-alt text-primary me-2"></i>
                    Additional Information
                </h6>
            </div>

            <div class="card-body">

                <label for="description" class="form-label">
                    Description
                </label>

                <textarea name="description"
                          id="description"
                          rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Enter transaction description...">{{ old('description') }}</textarea>

                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>
        </div>

    </div>


    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Account Snapshot --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-wallet text-primary me-2"></i>
                    Account Snapshot
                </h6>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <div class="text-muted small">Member</div>
                    <div class="fw-semibold" id="memberName">—</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Account Number</div>
                    <div class="fw-semibold" id="accountNumber">—</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Current Units</div>
                    <div class="fw-semibold" id="currentUnits">0</div>
                </div>

                <div>
                    <div class="text-muted small">Current Share Balance</div>
                    <div class="fw-semibold" id="currentBalance">
                        KES 0.00
                    </div>
                </div>

            </div>
        </div>


        {{-- New Position Preview --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-chart-line text-primary me-2"></i>
                    New Position
                </h6>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <div class="text-muted small">New Running Units</div>
                    <div class="fs-5 fw-bold" id="newUnits">—</div>
                </div>

                <div>
                    <div class="text-muted small">New Running Balance</div>
                    <div class="fs-5 fw-bold" id="newBalance">—</div>
                </div>

            </div>
        </div>


        {{-- Actions --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <button type="submit"
                        class="btn btn-primary w-100 mb-2"
                        id="saveBtn">
                    <i class="fa fa-save me-1"></i>
                    Post Transaction
                </button>

                <a href="{{ route('share_transactions.index') }}"
                   class="btn btn-outline-secondary w-100">
                    Cancel
                </a>

            </div>
        </div>

    </div>

</div>