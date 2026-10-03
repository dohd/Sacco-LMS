<div class="row g-4">

    {{-- Main Form --}}
    <div class="col-lg-8">

        {{-- Member & Product --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-user-circle text-primary me-2"></i>
                    Member & Share Product
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="member_id" class="form-label">
                            Member <span class="text-danger">*</span>
                        </label>

                        <select name="member_id"
                                id="member_id"
                                class="form-select @error('member_id') is-invalid @enderror"
                                required>

                            <option value="">Select member</option>

                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>                                                
                                    {{ $member->membership_number }} - {{ $member->full_name }}
                                </option>
                            @endforeach

                        </select>

                        @error('member_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="share_product_id" class="form-label">
                            Share Product <span class="text-danger">*</span>
                        </label>

                        <select name="share_product_id"
                                id="share_product_id"
                                class="form-select @error('share_product_id') is-invalid @enderror"
                                required>

                            <option value="">Select share product</option>

                            @foreach($shareProducts as $product)
                                <option value="{{ $product->id }}"
                                    data-unit-value="{{ $product->unit_value }}"
                                    data-minimum-units="{{ $product->minimum_units }}"
                                    data-maximum-units="{{ $product->maximum_units }}"
                                    {{ old('share_product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->code }} - {{ $product->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('share_product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

            </div>
        </div>


        {{-- Account Details --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-id-card text-primary me-2"></i>
                    Account Details
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="account_number" class="form-label">
                            Account Number <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="account_number"
                               id="account_number"
                               value="{{ old('account_number') }}"
                               class="form-control @error('account_number') is-invalid @enderror"
                               maxlength="255"
                               required>

                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            Must be unique.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="opened_date" class="form-label">
                            Opening Date <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="opened_date"
                               id="opened_date"
                               value="{{ old('opened_date', date('Y-m-d')) }}"
                               class="form-control @error('opened_date') is-invalid @enderror"
                               required>

                        @error('opened_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

            </div>
        </div>


        {{-- Opening Position --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-chart-line text-primary me-2"></i>
                    Opening Position
                </h6>
            </div>

            <div class="card-body">

                <div class="alert alert-info border-0 mb-3">
                    <i class="fa fa-info-circle me-2"></i>
                    Share balances should normally be created through the
                    <strong>share transaction workflow</strong>. They should not
                    be manually entered when opening the account.
                </div>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Initial Units</label>
                        <input type="number"
                               id="preview_units"
                               class="form-control"
                               min="0"
                               value="0">
                        <div class="form-text">
                            For calculation preview only.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Unit Value</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="text"
                                   id="preview_unit_value"
                                   class="form-control"
                                   value="0.00"
                                   readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Share Value</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="text"
                                   id="preview_share_value"
                                   class="form-control fw-bold"
                                   value="0.00"
                                   readonly>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>


    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Product Rules --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-sliders-h text-primary me-2"></i>
                    Product Rules
                </h6>
            </div>

            <div class="card-body">

                <div class="mb-3">
                    <div class="text-muted small">Minimum Units</div>
                    <div id="minimum_units" class="fw-semibold">—</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Maximum Units</div>
                    <div id="maximum_units" class="fw-semibold">—</div>
                </div>

                <div>
                    <div class="text-muted small">Unit Value</div>
                    <div id="product_unit_value" class="fw-semibold">—</div>
                </div>

            </div>
        </div>


        {{-- Status --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-toggle-on text-primary me-2"></i>
                    Account Status
                </h6>
            </div>

            <div class="card-body">
                <label for="status" class="form-label">
                    Status <span class="text-danger">*</span>
                </label>
                <select name="status"
                        id="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required>
                    <option value="active"
                        {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="frozen"
                        {{ old('status') === 'frozen' ? 'selected' : '' }}>
                        Frozen
                    </option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>


        {{-- Actions --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <button type="submit"
                        class="btn btn-primary w-100 mb-2"
                        id="saveBtn">
                    <i class="fa fa-save me-1"></i>
                    Save Record
                </button>

                <a href="{{ route('share_accounts.index') }}"
                   class="btn btn-outline-secondary w-100">
                    Cancel
                </a>

            </div>
        </div>

    </div>

</div>