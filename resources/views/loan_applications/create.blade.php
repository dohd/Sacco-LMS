@extends('layouts.core')
@section('title', 'Loan Applications | Create')

@section('content')
@include('loan_applications.partial.header')
<div class="container-fluid">
    @php
        $oldGuarantors = old('guarantors', []);
        $oldSecurities = old('securities', []);
        $oldWitnesses = old('witnesses', []);
    @endphp

    <form method="POST" action="{{ route('loan_applications.store') }}" enctype="multipart/form-data" id="loanApplicationForm">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please correct the highlighted errors.</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- APPLICATION -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Loan Application</h5>
                    <small class="text-muted">Member, product and requested loan terms</small>
                </div>
                <span class="badge bg-secondary">New Application</span>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Member <span class="text-danger">*</span></label>
                        <select name="member_id" id="member_id" class="form-select @error('member_id') is-invalid @enderror" required>
                            <option value="">Select Member</option>

                            @foreach($members as $member)
                                <option value="{{ $member->id }}"
                                    data-name="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                                    data-number="{{ $member->membership_number ?? '' }}"
                                    data-national-id="{{ $member->national_id ?? '' }}"
                                    data-shares="{{ $member->total_share_contribution ?? 0 }}"
                                    data-outstanding="{{ $member->outstanding_loan_balance ?? 0 }}"
                                    data-monthly-contribution="{{ $member->monthly_share_contribution ?? 0 }}"
                                    {{ old('member_id') == $member->id ? 'selected' : '' }}>
                                    {{ $member->membership_number ?? $member->id }} -
                                    {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                </option>
                            @endforeach
                        </select>
                        @error('member_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Loan Product <span class="text-danger">*</span></label>
                        <select name="loan_product_id" id="loan_product_id" class="form-select @error('loan_product_id') is-invalid @enderror" required>
                            <option value="">Select Loan Product</option>

                            @foreach($loanProducts as $product)
                                <option value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-min-amount="{{ $product->minimum_amount }}"
                                    data-max-amount="{{ $product->maximum_amount }}"
                                    data-min-months="{{ $product->minimum_repayment_months }}"
                                    data-max-months="{{ $product->maximum_repayment_months }}"
                                    data-interest-rate="{{ $product->interest_rate }}"
                                    data-interest-method="{{ $product->interest_method }}"
                                    data-interest-frequency="{{ $product->interest_frequency }}"
                                    data-requires-guarantors="{{ $product->requires_guarantors ? 1 : 0 }}"
                                    data-min-guarantors="{{ $product->minimum_guarantors }}"
                                    data-max-guarantors="{{ $product->maximum_guarantors }}"
                                    data-guarantor-coverage="{{ $product->minimum_guarantor_coverage_percentage }}"
                                    data-min-membership="{{ $product->minimum_membership_months }}"
                                    data-min-shares="{{ $product->minimum_share_contribution }}"
                                    data-min-monthly="{{ $product->minimum_monthly_contribution }}"
                                    data-share-multiplier="{{ $product->share_multiplier }}"
                                    data-application-fee="{{ $product->application_fee }}"
                                    data-processing-fee="{{ $product->processing_fee_percentage }}"
                                    data-insurance-fee="{{ $product->insurance_fee_percentage }}"
                                    data-grace-days="{{ $product->grace_period_days }}"
                                    {{ old('loan_product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->code }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('loan_product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div id="productRules" class="alert alert-light border d-none mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Loan Amount</small>
                            <strong id="ruleAmount">-</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Repayment</small>
                            <strong id="ruleMonths">-</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Interest</small>
                            <strong id="ruleInterest">-</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Guarantor Requirement</small>
                            <strong id="ruleGuarantors">-</strong>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount Requested <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="amount_requested" id="amount_requested" step="0.01" min="0.01"
                                class="form-control text-end @error('amount_requested') is-invalid @enderror"
                                value="{{ old('amount_requested') }}" required>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Repayment Period <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="repayment_period_months" id="repayment_period_months" min="1"
                                class="form-control @error('repayment_period_months') is-invalid @enderror"
                                value="{{ old('repayment_period_months') }}" required>
                            <span class="input-group-text">Months</span>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Estimated Monthly Installment</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="monthly_installment" id="monthly_installment" step="0.01"
                                class="form-control text-end bg-light"
                                value="{{ old('monthly_installment', 0) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Amount in Words</label>
                        <input type="text" name="amount_in_words" class="form-control"
                            value="{{ old('amount_in_words') }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Required Date</label>
                        <input type="date" name="required_date" class="form-control"
                            value="{{ old('required_date') }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="">Select</option>
                            <option value="standing_order" {{ old('payment_mode') === 'standing_order' ? 'selected' : '' }}>Standing Order</option>
                            <option value="check_off" {{ old('payment_mode') === 'check_off' ? 'selected' : '' }}>Check Off</option>
                            <option value="post_dated_cheques" {{ old('payment_mode') === 'post_dated_cheques' ? 'selected' : '' }}>Post Dated Cheques</option>
                            <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>

                    <div class="col-md-5 mb-3">
                        <label class="form-label">Loan Purpose <span class="text-danger">*</span></label>
                        <textarea name="loan_purpose" class="form-control" rows="3" required>{{ old('loan_purpose') }}</textarea>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Purpose Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="purpose_amount" step="0.01" min="0"
                                class="form-control text-end" value="{{ old('purpose_amount') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EMPLOYMENT / BUSINESS -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Employment & Business Information</h5>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Employment Type</label>
                        <select name="employment_type" id="employment_type" class="form-select">
                            <option value="">Select</option>
                            <option value="permanent" {{ old('employment_type') === 'permanent' ? 'selected' : '' }}>Permanent</option>
                            <option value="seasonal" {{ old('employment_type') === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                            <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                            <option value="self_employed" {{ old('employment_type') === 'self_employed' ? 'selected' : '' }}>Self Employed</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3 employed-field">
                        <label class="form-label">Employer Name</label>
                        <input type="text" name="employer_name" class="form-control" value="{{ old('employer_name') }}">
                    </div>

                    <div class="col-md-4 mb-3 employed-field">
                        <label class="form-label">Work Station</label>
                        <input type="text" name="work_station" class="form-control" value="{{ old('work_station') }}">
                    </div>

                    <div class="col-md-6 mb-3 employed-field">
                        <label class="form-label">Employer Postal Address</label>
                        <input type="text" name="employer_postal_address" class="form-control"
                            value="{{ old('employer_postal_address') }}">
                    </div>

                    <div class="col-md-6 mb-3 business-field">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" class="form-control"
                            value="{{ old('business_name') }}">
                    </div>

                    <div class="col-md-6 mb-3 business-field">
                        <label class="form-label">Business Postal Address</label>
                        <input type="text" name="business_postal_address" class="form-control"
                            value="{{ old('business_postal_address') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- FINANCIAL POSITION -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Member Financial Position</h5>
            </div>

            <div class="card-body">
                <div class="alert alert-info py-2">
                    These figures are populated from the selected member's records and should also be recalculated server-side when the application is submitted.
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Total Share Contribution</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="total_share_contribution" id="total_share_contribution"
                                class="form-control text-end bg-light" value="{{ old('total_share_contribution', 0) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Outstanding Loan Balance</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="outstanding_loan_balance" id="outstanding_loan_balance"
                                class="form-control text-end bg-light" value="{{ old('outstanding_loan_balance', 0) }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Monthly Share Contribution</label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="monthly_share_contribution" id="monthly_share_contribution"
                                class="form-control text-end bg-light" value="{{ old('monthly_share_contribution', 0) }}" readonly>
                        </div>
                    </div>
                </div>

                <div id="eligibilityWarnings"></div>
            </div>
        </div>

        <!-- GUARANTORS -->
        <div class="card shadow-sm mb-4" id="guarantorCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Loan Guarantors</h5>
                    <small class="text-muted" id="guarantorRequirementText">Add guarantors where required by the selected product.</small>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addGuarantor">
                    + Add Guarantor
                </button>
            </div>

            <div class="card-body">
                <div id="guarantorRows">
                    @foreach($oldGuarantors as $i => $guarantor)
                        <div class="guarantor-row border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Guarantor <span class="guarantor-number">{{ $loop->iteration }}</span></strong>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-guarantor">Remove</button>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Guarantor Member <span class="text-danger">*</span></label>
                                    <select name="guarantors[{{ $i }}][member_id]" class="form-select guarantor-member">
                                        <option value="">Select Member</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}"
                                                data-name="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                                                data-number="{{ $member->membership_number ?? '' }}"
                                                data-national-id="{{ $member->national_id ?? '' }}"
                                                data-shares="{{ $member->total_share_contribution ?? 0 }}"
                                                {{ ($guarantor['member_id'] ?? '') == $member->id ? 'selected' : '' }}>
                                                {{ $member->membership_number ?? $member->id }} -
                                                {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Guarantor Name</label>
                                    <input type="text" name="guarantors[{{ $i }}][guarantor_name]"
                                        class="form-control guarantor-name bg-light"
                                        value="{{ $guarantor['guarantor_name'] ?? '' }}" readonly>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Member Number</label>
                                    <input type="text" name="guarantors[{{ $i }}][member_number]"
                                        class="form-control guarantor-number-field bg-light"
                                        value="{{ $guarantor['member_number'] ?? '' }}" readonly>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">National ID</label>
                                    <input type="text" name="guarantors[{{ $i }}][national_id]"
                                        class="form-control guarantor-national-id bg-light"
                                        value="{{ $guarantor['national_id'] ?? '' }}" readonly>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Available Shares</label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" class="form-control guarantor-available-shares bg-light" readonly>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Shares Offered <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" name="guarantors[{{ $i }}][shares_offered]"
                                            class="form-control guarantor-shares" min="0" step="0.01"
                                            value="{{ $guarantor['shares_offered'] ?? 0 }}">
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Guarantor Signature</label>
                                    <input type="file" name="guarantors[{{ $i }}][signature]"
                                        class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Guarantor Witness Name</label>
                                    <input type="text" name="guarantors[{{ $i }}][witness_name]"
                                        class="form-control"
                                        value="{{ $guarantor['witness_name'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border rounded bg-light p-3">
                    <div class="row">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Required Coverage</small>
                            <strong id="requiredCoverage">KES 0.00</strong>
                        </div>

                        <div class="col-md-3">
                            <small class="text-muted d-block">Total Guaranteed</small>
                            <strong id="totalGuaranteed">KES 0.00</strong>
                        </div>

                        <div class="col-md-3">
                            <small class="text-muted d-block">Coverage</small>
                            <strong id="coveragePercentage">0.00%</strong>
                        </div>

                        <div class="col-md-3">
                            <small class="text-muted d-block">Guarantors</small>
                            <strong id="guarantorCount">0</strong>
                        </div>
                    </div>

                    <div id="guarantorValidation" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- SECURITIES -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Loan Security / Collateral</h5>
                    <small class="text-muted">Shares and additional collateral offered against the loan</small>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addSecurity">
                    + Add Security
                </button>
            </div>

            <div class="card-body">
                <div id="securityRows">
                    @foreach($oldSecurities as $i => $security)
                        <div class="security-row border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Security <span class="security-number">{{ $loop->iteration }}</span></strong>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-security">Remove</button>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Security Type <span class="text-danger">*</span></label>
                                    <select name="securities[{{ $i }}][security_type]" class="form-select">
                                        <option value="">Select</option>
                                        <option value="pledged_shares" {{ ($security['security_type'] ?? '') === 'pledged_shares' ? 'selected' : '' }}>Pledged Shares</option>
                                        <option value="additional_collateral" {{ ($security['security_type'] ?? '') === 'additional_collateral' ? 'selected' : '' }}>Additional Collateral</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Security Name <span class="text-danger">*</span></label>
                                    <input type="text" name="securities[{{ $i }}][security_name]"
                                        class="form-control"
                                        value="{{ $security['security_name'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Estimated Value <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" name="securities[{{ $i }}][security_value]"
                                            class="form-control security-value" min="0" step="0.01"
                                            value="{{ $security['security_value'] ?? 0 }}">
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" name="securities[{{ $i }}][reference_number]"
                                        class="form-control"
                                        value="{{ $security['reference_number'] ?? '' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="securities[{{ $i }}][description]"
                                        class="form-control" rows="2">{{ $security['description'] ?? '' }}</textarea>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Owner Name</label>
                                    <input type="text" name="securities[{{ $i }}][owner_name]"
                                        class="form-control"
                                        value="{{ $security['owner_name'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Owner National ID</label>
                                    <input type="text" name="securities[{{ $i }}][owner_national_id]"
                                        class="form-control"
                                        value="{{ $security['owner_national_id'] ?? '' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Supporting Document</label>
                                    <input type="file" name="securities[{{ $i }}][supporting_document]"
                                        class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="securities[{{ $i }}][remarks]"
                                        class="form-control" rows="2">{{ $security['remarks'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-end">
                    <span class="text-muted me-2">Total Declared Security:</span>
                    <strong id="totalSecurityValue">KES 0.00</strong>
                </div>
            </div>
        </div>

        <!-- WITNESSES -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Loan Witnesses</h5>
                    <small class="text-muted">Persons witnessing the applicant's declaration</small>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addWitness">
                    + Add Witness
                </button>
            </div>

            <div class="card-body">
                <div id="witnessRows">
                    @foreach($oldWitnesses as $i => $witness)
                        <div class="witness-row border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Witness <span class="witness-number">{{ $loop->iteration }}</span></strong>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-witness">Remove</button>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Member</label>
                                    <select name="witnesses[{{ $i }}][member_id]" class="form-select witness-member">
                                        <option value="">Not / Unknown Member</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->id }}"
                                                data-name="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                                                data-national-id="{{ $member->national_id ?? '' }}"
                                                data-phone="{{ $member->phone ?? '' }}"
                                                {{ ($witness['member_id'] ?? '') == $member->id ? 'selected' : '' }}>
                                                {{ $member->membership_number ?? $member->id }} -
                                                {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Witness Name <span class="text-danger">*</span></label>
                                    <input type="text" name="witnesses[{{ $i }}][name]"
                                        class="form-control witness-name"
                                        value="{{ $witness['name'] ?? '' }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">National ID <span class="text-danger">*</span></label>
                                    <input type="text" name="witnesses[{{ $i }}][national_id]"
                                        class="form-control witness-national-id"
                                        value="{{ $witness['national_id'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Payroll Number</label>
                                    <input type="text" name="witnesses[{{ $i }}][payroll_number]"
                                        class="form-control"
                                        value="{{ $witness['payroll_number'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Employer</label>
                                    <input type="text" name="witnesses[{{ $i }}][employer]"
                                        class="form-control"
                                        value="{{ $witness['employer'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Station</label>
                                    <input type="text" name="witnesses[{{ $i }}][station]"
                                        class="form-control"
                                        value="{{ $witness['station'] ?? '' }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="witnesses[{{ $i }}][phone]"
                                        class="form-control witness-phone"
                                        value="{{ $witness['phone'] ?? '' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="witnesses[{{ $i }}][address]"
                                        class="form-control" rows="2">{{ $witness['address'] ?? '' }}</textarea>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Signature</label>
                                    <input type="file" name="witnesses[{{ $i }}][signature]"
                                        class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Signed Date</label>
                                    <input type="date" name="witnesses[{{ $i }}][signed_at]"
                                        class="form-control"
                                        value="{{ $witness['signed_at'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- DECLARATION -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Applicant Declaration</h5>
            </div>

            <div class="card-body">
                <div class="alert alert-light border">
                    I declare that the information supplied in this application is true and authorize the cooperative to verify the information, guarantors and securities provided.
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Applicant Signature</label>
                        <input type="file" name="applicant_signature" class="form-control"
                            accept=".jpg,.jpeg,.png,.pdf">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Declaration Date</label>
                        <input type="date" name="declaration_date" class="form-control"
                            value="{{ old('declaration_date', now()->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- APPLICATION CHECK -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Application Check</h5>
            </div>

            <div class="card-body">
                <div id="applicationCheck"></div>

                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Amount Requested</small>
                        <strong id="summaryAmount">KES 0.00</strong>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted d-block">Monthly Installment</small>
                        <strong id="summaryInstallment">KES 0.00</strong>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted d-block">Guarantor Security</small>
                        <strong id="summaryGuarantors">KES 0.00</strong>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted d-block">Other Security</small>
                        <strong id="summarySecurity">KES 0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('loan_applications.index') }}" class="btn btn-light">Cancel</a>

                <div>
                    <button type="submit" name="submission_action" value="draft" class="btn btn-secondary">
                        Save Draft
                    </button>

                    <button type="submit" name="submission_action" value="submit" class="btn btn-primary" id="submitApplication">
                        Submit Application
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- GUARANTOR TEMPLATE -->
<template id="guarantorTemplate">
    <div class="guarantor-row border rounded p-3 mb-3">
        <div class="d-flex justify-content-between mb-3">
            <strong>Guarantor <span class="guarantor-number"></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-guarantor">Remove</button>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Guarantor Member <span class="text-danger">*</span></label>
                <select data-name="member_id" class="form-select guarantor-member">
                    <option value="">Select Member</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}"
                            data-name="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                            data-number="{{ $member->membership_number ?? '' }}"
                            data-national-id="{{ $member->national_id ?? '' }}"
                            data-shares="{{ $member->total_share_contribution ?? 0 }}">
                            {{ $member->membership_number ?? $member->id }} -
                            {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Guarantor Name</label>
                <input type="text" data-name="guarantor_name" class="form-control guarantor-name bg-light" readonly>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Member Number</label>
                <input type="text" data-name="member_number" class="form-control guarantor-number-field bg-light" readonly>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">National ID</label>
                <input type="text" data-name="national_id" class="form-control guarantor-national-id bg-light" readonly>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Available Shares</label>
                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number" class="form-control guarantor-available-shares bg-light" readonly>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Shares Offered <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number" data-name="shares_offered" class="form-control guarantor-shares" min="0" step="0.01" value="0">
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Guarantor Signature</label>
                <input type="file" data-name="signature" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Guarantor Witness Name</label>
                <input type="text" data-name="witness_name" class="form-control">
            </div>
        </div>
    </div>
</template>

<!-- SECURITY TEMPLATE -->
<template id="securityTemplate">
    <div class="security-row border rounded p-3 mb-3">
        <div class="d-flex justify-content-between mb-3">
            <strong>Security <span class="security-number"></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-security">Remove</button>
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Security Type <span class="text-danger">*</span></label>
                <select data-name="security_type" class="form-select security-type">
                    <option value="">Select</option>
                    <option value="pledged_shares">Pledged Shares</option>
                    <option value="additional_collateral">Additional Collateral</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Security Name <span class="text-danger">*</span></label>
                <input type="text" data-name="security_name" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Estimated Value <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">KES</span>
                    <input type="number" data-name="security_value" class="form-control security-value" min="0" step="0.01" value="0">
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Reference Number</label>
                <input type="text" data-name="reference_number" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Description</label>
                <textarea data-name="description" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Owner Name</label>
                <input type="text" data-name="owner_name" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Owner National ID</label>
                <input type="text" data-name="owner_national_id" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Supporting Document</label>
                <input type="file" data-name="supporting_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Remarks</label>
                <textarea data-name="remarks" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>
</template>

<!-- WITNESS TEMPLATE -->
<template id="witnessTemplate">
    <div class="witness-row border rounded p-3 mb-3">
        <div class="d-flex justify-content-between mb-3">
            <strong>Witness <span class="witness-number"></span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-witness">Remove</button>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Member</label>
                <select data-name="member_id" class="form-select witness-member">
                    <option value="">Not / Unknown Member</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}"
                            data-name="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                            data-national-id="{{ $member->national_id ?? '' }}"
                            data-phone="{{ $member->phone ?? '' }}">
                            {{ $member->membership_number ?? $member->id }} -
                            {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Witness Name <span class="text-danger">*</span></label>
                <input type="text" data-name="name" class="form-control witness-name">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">National ID <span class="text-danger">*</span></label>
                <input type="text" data-name="national_id" class="form-control witness-national-id">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Payroll Number</label>
                <input type="text" data-name="payroll_number" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Employer</label>
                <input type="text" data-name="employer" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Station</label>
                <input type="text" data-name="station" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" data-name="phone" class="form-control witness-phone">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Address</label>
                <textarea data-name="address" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Signature</label>
                <input type="file" data-name="signature" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Signed Date</label>
                <input type="date" data-name="signed_at" class="form-control">
            </div>
        </div>
    </div>
</template>
@endsection

@section('script')
<script>
$(function () {
    let guarantorIndex = {{ count($oldGuarantors) }};
    let securityIndex = {{ count($oldSecurities) }};
    let witnessIndex = {{ count($oldWitnesses) }};

    function money(value) {
        return 'KES ' + Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function assignNames(row, collection, index) {
        row.find('[data-name]').each(function () {
            $(this).attr('name', collection + '[' + index + '][' + $(this).data('name') + ']');
        });
    }

    function renumberRows() {
        $('.guarantor-row').each(function (i) {
            $(this).find('.guarantor-number').text(i + 1);
        });

        $('.security-row').each(function (i) {
            $(this).find('.security-number').text(i + 1);
        });

        $('.witness-row').each(function (i) {
            $(this).find('.witness-number').text(i + 1);
        });
    }

    function selectedProduct() {
        return $('#loan_product_id option:selected');
    }

    function loadMemberFinancials() {
        const option = $('#member_id option:selected');

        $('#total_share_contribution').val(parseFloat(option.data('shares')) || 0);
        $('#outstanding_loan_balance').val(parseFloat(option.data('outstanding')) || 0);
        $('#monthly_share_contribution').val(parseFloat(option.data('monthly-contribution')) || 0);

        checkEligibility();
    }

    function showProductRules() {
        const option = selectedProduct();

        if (!option.val()) {
            $('#productRules').addClass('d-none');
            return;
        }

        const minAmount = parseFloat(option.data('min-amount')) || 0;
        const maxAmount = parseFloat(option.data('max-amount')) || 0;
        const minMonths = parseInt(option.data('min-months')) || 0;
        const maxMonths = parseInt(option.data('max-months')) || 0;
        const rate = parseFloat(option.data('interest-rate')) || 0;
        const method = String(option.data('interest-method') || '').replaceAll('_', ' ');
        const frequency = String(option.data('interest-frequency') || '').replaceAll('_', ' ');
        const requiresGuarantors = parseInt(option.data('requires-guarantors')) === 1;
        const minGuarantors = parseInt(option.data('min-guarantors')) || 0;
        const maxGuarantors = parseInt(option.data('max-guarantors')) || 0;
        const coverage = parseFloat(option.data('guarantor-coverage')) || 0;

        $('#ruleAmount').text(
            maxAmount > 0
                ? money(minAmount) + ' - ' + money(maxAmount)
                : 'From ' + money(minAmount)
        );

        $('#ruleMonths').text(minMonths + ' - ' + maxMonths + ' months');
        $('#ruleInterest').text(rate.toFixed(4) + '% ' + method + ' / ' + frequency);

        if (requiresGuarantors) {
            let text = 'Min ' + minGuarantors;

            if (maxGuarantors > 0) {
                text += ', Max ' + maxGuarantors;
            }

            text += ', ' + coverage.toFixed(2) + '% coverage';

            $('#ruleGuarantors').text(text);
            $('#guarantorRequirementText').text(text);
            $('#guarantorCard').show();
        } else {
            $('#ruleGuarantors').text('Not required');
            $('#guarantorRequirementText').text('This loan product does not require guarantors.');
        }

        $('#productRules').removeClass('d-none');

        calculateInstallment();
        calculateGuarantorCoverage();
        checkEligibility();
    }

    function calculateInstallment() {
        const option = selectedProduct();
        const amount = parseFloat($('#amount_requested').val()) || 0;
        const months = parseInt($('#repayment_period_months').val()) || 0;
        const rate = parseFloat(option.data('interest-rate')) || 0;
        const method = option.data('interest-method');
        const frequency = option.data('interest-frequency');

        if (!option.val() || amount <= 0 || months <= 0) {
            $('#monthly_installment').val('0.00');
            updateSummary();
            return;
        }

        let installment = 0;

        if (method === 'flat_rate') {
            let interest = 0;

            if (frequency === 'annual') {
                interest = amount * (rate / 100) * (months / 12);
            } else if (frequency === 'monthly') {
                interest = amount * (rate / 100) * months;
            } else {
                interest = amount * (rate / 100);
            }

            installment = (amount + interest) / months;
        } else {
            let monthlyRate = 0;

            if (frequency === 'annual') {
                monthlyRate = (rate / 100) / 12;
            } else if (frequency === 'monthly') {
                monthlyRate = rate / 100;
            } else if (frequency === 'one_time') {
                const total = amount + (amount * rate / 100);
                installment = total / months;
            }

            if (frequency !== 'one_time') {
                if (monthlyRate > 0) {
                    installment = amount * monthlyRate * Math.pow(1 + monthlyRate, months)
                        / (Math.pow(1 + monthlyRate, months) - 1);
                } else {
                    installment = amount / months;
                }
            }
        }

        $('#monthly_installment').val(installment.toFixed(2));
        updateSummary();
    }

    function checkEligibility() {
        const option = selectedProduct();

        if (!option.val() || !$('#member_id').val()) {
            $('#eligibilityWarnings').html('');
            return;
        }

        const shares = parseFloat($('#total_share_contribution').val()) || 0;
        const monthlyContribution = parseFloat($('#monthly_share_contribution').val()) || 0;
        const requested = parseFloat($('#amount_requested').val()) || 0;

        const minimumShares = parseFloat(option.data('min-shares')) || 0;
        const minimumMonthly = parseFloat(option.data('min-monthly')) || 0;
        const multiplier = parseFloat(option.data('share-multiplier')) || 0;

        let warnings = [];

        if (shares < minimumShares) {
            warnings.push('Member shares are below the minimum requirement of ' + money(minimumShares) + '.');
        }

        if (monthlyContribution < minimumMonthly) {
            warnings.push('Monthly contribution is below the required ' + money(minimumMonthly) + '.');
        }

        if (multiplier > 0 && requested > shares * multiplier) {
            warnings.push(
                'Requested amount exceeds the ' + multiplier.toFixed(2) +
                'x share limit of ' + money(shares * multiplier) + '.'
            );
        }

        if (warnings.length) {
            $('#eligibilityWarnings').html(
                '<div class="alert alert-warning mb-0"><strong>Eligibility warnings:</strong><ul class="mb-0 mt-1"><li>' +
                warnings.join('</li><li>') +
                '</li></ul></div>'
            );
        } else {
            $('#eligibilityWarnings').html(
                '<div class="alert alert-success mb-0">Basic contribution eligibility checks passed.</div>'
            );
        }
    }

    function calculateGuarantorCoverage() {
        const option = selectedProduct();
        const amount = parseFloat($('#amount_requested').val()) || 0;
        const requiredPercent = parseFloat(option.data('guarantor-coverage')) || 0;
        const requiresGuarantors = parseInt(option.data('requires-guarantors')) === 1;
        const minGuarantors = parseInt(option.data('min-guarantors')) || 0;
        const maxGuarantors = parseInt(option.data('max-guarantors')) || 0;

        let total = 0;
        let count = 0;

        $('.guarantor-row').each(function () {
            const memberId = $(this).find('.guarantor-member').val();
            const offered = parseFloat($(this).find('.guarantor-shares').val()) || 0;

            if (memberId) {
                count++;
                total += offered;
            }
        });

        const required = requiresGuarantors ? amount * (requiredPercent / 100) : 0;
        const coverage = amount > 0 ? (total / amount) * 100 : 0;

        $('#requiredCoverage').text(money(required));
        $('#totalGuaranteed').text(money(total));
        $('#coveragePercentage').text(coverage.toFixed(2) + '%');
        $('#guarantorCount').text(count);

        let message = '';

        if (!requiresGuarantors) {
            message = '<div class="text-success">Guarantors are not required for this product.</div>';
        } else if (count < minGuarantors) {
            message = '<div class="text-danger">At least ' + minGuarantors + ' guarantor(s) are required.</div>';
        } else if (maxGuarantors > 0 && count > maxGuarantors) {
            message = '<div class="text-danger">Maximum allowed guarantors: ' + maxGuarantors + '.</div>';
        } else if (total < required) {
            message = '<div class="text-warning">Guarantor coverage is below the required amount.</div>';
        } else {
            message = '<div class="text-success">Guarantor requirement satisfied.</div>';
        }

        $('#guarantorValidation').html(message);
        $('input[name="guarantor_security"]').val(total);

        updateSummary();
    }

    function calculateSecurity() {
        let total = 0;

        $('.security-value').each(function () {
            total += parseFloat($(this).val()) || 0;
        });

        $('#totalSecurityValue').text(money(total));
        updateSummary();
    }

    function updateSummary() {
        let guarantors = 0;
        let securities = 0;

        $('.guarantor-shares').each(function () {
            guarantors += parseFloat($(this).val()) || 0;
        });

        $('.security-value').each(function () {
            securities += parseFloat($(this).val()) || 0;
        });

        $('#summaryAmount').text(money($('#amount_requested').val()));
        $('#summaryInstallment').text(money($('#monthly_installment').val()));
        $('#summaryGuarantors').text(money(guarantors));
        $('#summarySecurity').text(money(securities));
    }

    function toggleEmploymentFields() {
        const selfEmployed = $('#employment_type').val() === 'self_employed';

        $('.business-field').toggle(selfEmployed);
        $('.employed-field').toggle(!selfEmployed);
    }

    $('#addGuarantor').on('click', function () {
        const row = $($('#guarantorTemplate').html());

        assignNames(row, 'guarantors', guarantorIndex++);
        $('#guarantorRows').append(row);

        renumberRows();
        calculateGuarantorCoverage();
    });

    $('#addSecurity').on('click', function () {
        const row = $($('#securityTemplate').html());

        assignNames(row, 'securities', securityIndex++);
        $('#securityRows').append(row);

        renumberRows();
    });

    $('#addWitness').on('click', function () {
        const row = $($('#witnessTemplate').html());

        assignNames(row, 'witnesses', witnessIndex++);
        $('#witnessRows').append(row);

        renumberRows();
    });

    $(document).on('click', '.remove-guarantor', function () {
        $(this).closest('.guarantor-row').remove();
        renumberRows();
        calculateGuarantorCoverage();
    });

    $(document).on('click', '.remove-security', function () {
        $(this).closest('.security-row').remove();
        renumberRows();
        calculateSecurity();
    });

    $(document).on('click', '.remove-witness', function () {
        $(this).closest('.witness-row').remove();
        renumberRows();
    });

    $(document).on('change', '.guarantor-member', function () {
        const row = $(this).closest('.guarantor-row');
        const option = $(this).find('option:selected');

        row.find('.guarantor-name').val(option.data('name') || '');
        row.find('.guarantor-number-field').val(option.data('number') || '');
        row.find('.guarantor-national-id').val(option.data('national-id') || '');
        row.find('.guarantor-available-shares').val(parseFloat(option.data('shares')) || 0);

        calculateGuarantorCoverage();
    });

    $(document).on('input', '.guarantor-shares', function () {
        const row = $(this).closest('.guarantor-row');
        const available = parseFloat(row.find('.guarantor-available-shares').val()) || 0;
        const offered = parseFloat($(this).val()) || 0;

        $(this).toggleClass('is-invalid', offered > available);
        calculateGuarantorCoverage();
    });

    $(document).on('change', '.witness-member', function () {
        const row = $(this).closest('.witness-row');
        const option = $(this).find('option:selected');

        if ($(this).val()) {
            row.find('.witness-name').val(option.data('name') || '');
            row.find('.witness-national-id').val(option.data('national-id') || '');
            row.find('.witness-phone').val(option.data('phone') || '');
        }
    });

    $(document).on('input', '.security-value', calculateSecurity);

    $('#member_id').on('change', loadMemberFinancials);

    $('#loan_product_id').on('change', showProductRules);

    $('#amount_requested, #repayment_period_months').on('input', function () {
        calculateInstallment();
        calculateGuarantorCoverage();
        checkEligibility();
    });

    $('#employment_type').on('change', toggleEmploymentFields);

    $('#loanApplicationForm').on('submit', function (e) {
        const action = $(document.activeElement).val();

        if (action === 'draft') {
            return true;
        }

        const option = selectedProduct();
        const amount = parseFloat($('#amount_requested').val()) || 0;
        const months = parseInt($('#repayment_period_months').val()) || 0;
        const minAmount = parseFloat(option.data('min-amount')) || 0;
        const maxAmount = parseFloat(option.data('max-amount')) || 0;
        const minMonths = parseInt(option.data('min-months')) || 0;
        const maxMonths = parseInt(option.data('max-months')) || 0;

        if (!$('#member_id').val() || !$('#loan_product_id').val()) {
            alert('Please select the member and loan product.');
            e.preventDefault();
            return false;
        }

        if (amount < minAmount || (maxAmount > 0 && amount > maxAmount)) {
            alert('Requested amount is outside the selected loan product limits.');
            e.preventDefault();
            return false;
        }

        if (months < minMonths || months > maxMonths) {
            alert('Repayment period is outside the selected loan product limits.');
            e.preventDefault();
            return false;
        }

        const requiresGuarantors = parseInt(option.data('requires-guarantors')) === 1;

        if (requiresGuarantors) {
            const minimum = parseInt(option.data('min-guarantors')) || 0;
            const maximum = parseInt(option.data('max-guarantors')) || 0;
            const requiredPercentage = parseFloat(option.data('guarantor-coverage')) || 0;

            let count = 0;
            let guaranteed = 0;

            $('.guarantor-row').each(function () {
                if ($(this).find('.guarantor-member').val()) {
                    count++;
                    guaranteed += parseFloat($(this).find('.guarantor-shares').val()) || 0;
                }
            });

            const requiredAmount = amount * requiredPercentage / 100;

            if (count < minimum) {
                alert('This loan product requires at least ' + minimum + ' guarantor(s).');
                e.preventDefault();
                return false;
            }

            if (maximum > 0 && count > maximum) {
                alert('This loan product allows a maximum of ' + maximum + ' guarantors.');
                e.preventDefault();
                return false;
            }

            if (guaranteed < requiredAmount) {
                alert('Guarantor security does not meet the required coverage.');
                e.preventDefault();
                return false;
            }
        }

        return true;
    });

    $('.guarantor-member').trigger('change');
    $('.witness-member').trigger('change');

    renumberRows();
    loadMemberFinancials();
    showProductRules();
    toggleEmploymentFields();
    calculateSecurity();
});
</script>
@endsection