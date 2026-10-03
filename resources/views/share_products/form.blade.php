<div class="row g-3">


    {{-- ========================================================= --}}
    {{-- BASIC PRODUCT INFORMATION --}}
    {{-- ========================================================= --}}

    <div class="col-12">

        <div class="card shadow-sm">

            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-layer-group me-1"></i>
                    Product Information
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- Code --}}
                    <div class="col-md-3">

                        <label for="code" class="form-label">
                            Product Code <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="code"
                               id="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}"
                               maxlength="50"
                               required>

                        @error('code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Name --}}
                    <div class="col-md-5">

                        <label for="name" class="form-label">
                            Product Name <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               maxlength="255"
                               required>

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>                                


                    {{-- Description --}}
                    <div class="col-12">

                        <label for="description" class="form-label">
                            Description
                        </label>

                        <textarea name="description"
                                  id="description"
                                  rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe this share product...">{{ old('description') }}</textarea>

                        @error('description')
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
    {{-- SHARE VALUE & LIMITS --}}
    {{-- ========================================================= --}}

    <div class="col-lg-7">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-coins me-1"></i>
                    Share Value & Limits
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- Unit Value --}}
                    <div class="col-md-6">

                        <label for="unit_value" class="form-label">
                            Unit Value <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                KES
                            </span>

                            <input type="number"
                                   name="unit_value"
                                   id="unit_value"
                                   class="form-control @error('unit_value') is-invalid @enderror"
                                   value="{{ old('unit_value') }}"
                                   min="0.01"
                                   step="0.01"
                                   required>

                        </div>

                        <small class="text-muted">
                            Nominal value of one share unit.
                        </small>

                        @error('unit_value')
                            <div class="text-danger small">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Minimum Units --}}
                    <div class="col-md-3">

                        <label for="minimum_units" class="form-label">
                            Minimum Units
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               name="minimum_units"
                               id="minimum_units"
                               class="form-control @error('minimum_units') is-invalid @enderror"
                               value="{{ old('minimum_units', 1) }}"
                               min="1"
                               step="1"
                               required>

                        @error('minimum_units')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Maximum Units --}}
                    <div class="col-md-3">

                        <label for="maximum_units" class="form-label">
                            Maximum Units
                        </label>

                        <input type="number"
                               name="maximum_units"
                               id="maximum_units"
                               class="form-control @error('maximum_units') is-invalid @enderror"
                               value="{{ old('maximum_units') }}"
                               min="1"
                               step="1">

                        <small class="text-muted">
                            Leave blank for unlimited.
                        </small>

                        @error('maximum_units')
                            <div class="text-danger small">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Calculated Example --}}
                <div class="alert alert-light border mt-3 mb-0">

                    <div class="d-flex justify-content-between">

                        <span>
                            Minimum Share Value
                        </span>

                        <strong id="minimumShareValue">
                            KES 0.00
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mt-2">

                        <span>
                            Maximum Share Value
                        </span>

                        <strong id="maximumShareValue">
                            Unlimited
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SHARE FEATURES --}}
    {{-- ========================================================= --}}

    <div class="col-lg-5">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-white">
                <strong>
                    <i class="fa fa-sliders-h me-1"></i>
                    Share Features
                </strong>
            </div>

            <div class="card-body">

                <div class="form-check form-switch mb-3">

                    <input class="form-check-input"
                           type="checkbox"
                           name="dividend_eligible"
                           value="1"
                           id="dividend_eligible"
                           {{ old('dividend_eligible', 1) ? 'checked' : '' }}>

                    <label class="form-check-label"
                           for="dividend_eligible">

                        <strong>Dividend Eligible</strong>

                        <div class="text-muted small">
                            Shares qualify for dividend allocation.
                        </div>

                    </label>

                </div>


                <div class="form-check form-switch mb-3">

                    <input class="form-check-input"
                           type="checkbox"
                           name="allows_transfer"
                           value="1"
                           id="allows_transfer"
                           {{ old('allows_transfer') ? 'checked' : '' }}>

                    <label class="form-check-label"
                           for="allows_transfer">

                        <strong>Allow Transfers</strong>

                        <div class="text-muted small">
                            Members can transfer shares.
                        </div>

                    </label>

                </div>


                <div class="form-check form-switch mb-3">

                    <input class="form-check-input"
                           type="checkbox"
                           name="allows_redemption"
                           value="1"
                           id="allows_redemption"
                           {{ old('allows_redemption') ? 'checked' : '' }}>

                    <label class="form-check-label"
                           for="allows_redemption">

                        <strong>Allow Redemption</strong>

                        <div class="text-muted small">
                            Members can redeem shares.
                        </div>

                    </label>

                </div>


                <div class="form-check form-switch">

                    <input class="form-check-input"
                           type="checkbox"
                           name="can_secure_loan"
                           value="1"
                           id="can_secure_loan"
                           {{ old('can_secure_loan', 1) ? 'checked' : '' }}>

                    <label class="form-check-label"
                           for="can_secure_loan">

                        <strong>Can Secure Loan</strong>

                        <div class="text-muted small">
                            Shares can be used as loan security.
                        </div>

                    </label>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- GENERAL LEDGER MAPPING --}}
    {{-- ========================================================= --}}

    <div class="col-12">

        <div class="card shadow-sm">

            <div class="card-header bg-white">

                <strong>
                    <i class="fa fa-book me-1"></i>
                    General Ledger Mapping
                </strong>

                <div class="text-muted small">
                    Select the accounts that will be used for share accounting.
                </div>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    {{-- Share Capital --}}
                    <div class="col-md-6">
                        <label for="share_capital_account_id"
                               class="form-label">
                            Share Capital Account
                            <span class="text-danger">*</span>
                        </label>
                        <select name="share_capital_account_id"
                                id="share_capital_account_id"
                                class="form-select @error('share_capital_account_id') is-invalid @enderror"
                                >
                            <option value="0">
                                Select Share Capital Account
                            </option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ old('share_capital_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->code ?? $account->id }}
                                    -
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('share_capital_account_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                    {{-- Share Premium --}}
                    <div class="col-md-6">
                        <label for="share_premium_account_id"
                               class="form-label">
                            Share Premium Account
                            <span class="text-danger">*</span>
                        </label>
                        <select name="share_premium_account_id"
                                id="share_premium_account_id"
                                class="form-select @error('share_premium_account_id') is-invalid @enderror"
                                >
                            <option value="0">
                                Select Share Premium Account
                            </option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ old('share_premium_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->code ?? $account->id }}
                                    -
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('share_premium_account_id')
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
    {{-- SUBMIT --}}
    {{-- ========================================================= --}}

    <div class="col-12">

        <div class="card shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div class="text-muted small">

                        Review the product configuration before saving.

                    </div>

                    <div class="d-flex gap-2">

                        <a href="{{ route('share_products.index') }}"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                        <button type="submit"
                                class="btn btn-primary"
                                id="submitBtn">

                            <i class="fa fa-save me-1"></i>
                            Save Record

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>