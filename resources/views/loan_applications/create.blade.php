@extends('layouts.core')

@section('title', 'Loan Applications')

@section('content')
    @include('loan_applications.header')
    <style>
        body {
            background-color: #f4f6f9;
        }

        .loan-form-container {
            max-width: 1150px;
        }

        .form-header {
            padding: 30px;
            margin-bottom: 24px;
            color: #ffffff;
            background-color: #198754;
            border-radius: 12px;
        }

        .form-card {
            margin-bottom: 24px;
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .form-card .card-header {
            padding: 18px 22px;
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
        }

        .form-card .card-body {
            padding: 24px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-right: 10px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            background-color: #198754;
            border-radius: 50%;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .optional-section {
            display: none;
        }

        .signature-preview {
            display: none;
            width: 100%;
            max-width: 220px;
            height: 120px;
            margin-top: 12px;
            padding: 4px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: contain;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .submit-section {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding: 15px 0;
            background-color: rgba(244, 246, 249, 0.95);
        }
    </style>
    <div class="container loan-form-container py-5">

        <div class="form-header">
            {{-- <h2 class="mb-2">Loan Application Form</h2> --}}
            <p class="mb-0">
                Complete the form below and provide accurate financial and security information.
            </p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <h6 class="alert-heading">Please correct the following errors:</h6>

                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            id="loanApplicationForm"
            action="{{ route('loan_applications.store') }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            <input
                type="hidden"
                name="member_id"
                value="{{ old('member_id', $member->id) }}"
            >

            <!-- Application and Member Details -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">1</span>
                        Application and Member Details
                    </h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        Member information is obtained from the selected or logged-in member record.
                    </div>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label for="application_number" class="form-label">
                                Application Number
                            </label>

                            <input
                                type="text"
                                name="application_number"
                                id="application_number"
                                value="{{ old('application_number', @$applicationNumber) }}"
                                class="form-control @error('application_number') is-invalid @enderror"
                                readonly
                            >

                            @error('application_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Membership Number</label>

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $member->membership_number ?? $member->id }}"
                                readonly
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Member Name</label>

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->middle_name ?? '') . ' ' . ($member->last_name ?? '')) }}"
                                readonly
                            >
                        </div>

                    </div>
                </div>
            </div>

            <!-- Loan Details -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">2</span>
                        Loan Details
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="amount_requested" class="form-label required-label">
                                Amount Requested
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="amount_requested"
                                    id="amount_requested"
                                    value="{{ old('amount_requested') }}"
                                    min="1"
                                    step="0.01"
                                    class="form-control @error('amount_requested') is-invalid @enderror"
                                    required
                                >

                                @error('amount_requested')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="repayment_period_months" class="form-label required-label">
                                Repayment Period
                            </label>

                            <div class="input-group">
                                <input
                                    type="number"
                                    name="repayment_period_months"
                                    id="repayment_period_months"
                                    value="{{ old('repayment_period_months') }}"
                                    min="1"
                                    max="120"
                                    class="form-control @error('repayment_period_months') is-invalid @enderror"
                                    required
                                >

                                <span class="input-group-text">Months</span>

                                @error('repayment_period_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="monthly_installment" class="form-label required-label">
                                Estimated Monthly Installment
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="monthly_installment"
                                    id="monthly_installment"
                                    value="{{ old('monthly_installment') }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('monthly_installment') is-invalid @enderror"
                                    readonly
                                    required
                                >

                                @error('monthly_installment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <small class="text-muted">
                                Calculated as amount requested divided by the repayment period. Interest can be applied server-side.
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label for="required_date" class="form-label">
                                Date Loan Is Required
                            </label>

                            <input
                                type="date"
                                name="required_date"
                                id="required_date"
                                value="{{ old('required_date') }}"
                                min="{{ now()->toDateString() }}"
                                class="form-control @error('required_date') is-invalid @enderror"
                            >

                            @error('required_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="payment_mode" class="form-label required-label">
                                Repayment Mode
                            </label>

                            <select
                                name="payment_mode"
                                id="payment_mode"
                                class="form-select @error('payment_mode') is-invalid @enderror"
                                required
                            >
                                <option value="">Select repayment mode</option>

                                <option
                                    value="standing_order"
                                    @selected(old('payment_mode') === 'standing_order')
                                >
                                    Standing Order
                                </option>

                                <option
                                    value="check_off"
                                    @selected(old('payment_mode') === 'check_off')
                                >
                                    Check-off
                                </option>

                                <option
                                    value="post_dated_cheques"
                                    @selected(old('payment_mode') === 'post_dated_cheques')
                                >
                                    Post-dated Cheques
                                </option>

                                <option
                                    value="cash"
                                    @selected(old('payment_mode') === 'cash')
                                >
                                    Cash
                                </option>
                            </select>

                            @error('payment_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="purpose_amount" class="form-label">
                                Amount Allocated to Purpose
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="purpose_amount"
                                    id="purpose_amount"
                                    value="{{ old('purpose_amount') }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('purpose_amount') is-invalid @enderror"
                                >

                                @error('purpose_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="amount_in_words" class="form-label">
                                Amount in Words
                            </label>

                            <textarea
                                name="amount_in_words"
                                id="amount_in_words"
                                rows="2"
                                class="form-control @error('amount_in_words') is-invalid @enderror"
                                readonly
                            >{{ old('amount_in_words') }}</textarea>

                            @error('amount_in_words')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="loan_purpose" class="form-label required-label">
                                Purpose of the Loan
                            </label>

                            <textarea
                                name="loan_purpose"
                                id="loan_purpose"
                                rows="4"
                                class="form-control @error('loan_purpose') is-invalid @enderror"
                                placeholder="Provide a clear description of how the loan will be used."
                                required
                            >{{ old('loan_purpose') }}</textarea>

                            @error('loan_purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card form-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <h5 class="mb-0">
                        <span class="section-number">3</span>
                        Employment Details
                    </h5>

                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            id="isEmployed"
                            class="form-check-input"
                            @checked(
                                old('employer_name') ||
                                old('employment_type') ||
                                old('work_station')
                            )
                        >

                        <label for="isEmployed" class="form-check-label">
                            Currently employed
                        </label>
                    </div>
                </div>

                <div id="employmentSection" class="card-body optional-section">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="employer_name" class="form-label">
                                Employer Name
                            </label>

                            <input
                                type="text"
                                name="employer_name"
                                id="employer_name"
                                value="{{ old('employer_name') }}"
                                class="form-control @error('employer_name') is-invalid @enderror"
                            >

                            @error('employer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="employment_type" class="form-label">
                                Employment Type
                            </label>

                            <select
                                name="employment_type"
                                id="employment_type"
                                class="form-select @error('employment_type') is-invalid @enderror"
                            >
                                <option value="">Select employment type</option>

                                <option
                                    value="permanent"
                                    @selected(old('employment_type') === 'permanent')
                                >
                                    Permanent
                                </option>

                                <option
                                    value="seasonal"
                                    @selected(old('employment_type') === 'seasonal')
                                >
                                    Seasonal
                                </option>

                                <option
                                    value="contract"
                                    @selected(old('employment_type') === 'contract')
                                >
                                    Contract
                                </option>

                                <option
                                    value="self_employed"
                                    @selected(old('employment_type') === 'self_employed')
                                >
                                    Self-employed
                                </option>
                            </select>

                            @error('employment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="work_station" class="form-label">
                                Work Station
                            </label>

                            <input
                                type="text"
                                name="work_station"
                                id="work_station"
                                value="{{ old('work_station') }}"
                                class="form-control @error('work_station') is-invalid @enderror"
                            >

                            @error('work_station')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="employer_postal_address" class="form-label">
                                Employer Postal Address
                            </label>

                            <input
                                type="text"
                                name="employer_postal_address"
                                id="employer_postal_address"
                                value="{{ old('employer_postal_address') }}"
                                class="form-control @error('employer_postal_address') is-invalid @enderror"
                            >

                            @error('employer_postal_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <!-- Business Details -->
            <div class="card form-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <h5 class="mb-0">
                        <span class="section-number">4</span>
                        Business Details
                    </h5>

                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            id="ownsBusiness"
                            class="form-check-input"
                            @checked(
                                old('business_name') ||
                                old('business_postal_address') ||
                                old('employment_type') === 'self_employed'
                            )
                        >

                        <label for="ownsBusiness" class="form-check-label">
                            Owns or operates a business
                        </label>
                    </div>
                </div>

                <div id="businessSection" class="card-body optional-section">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="business_name" class="form-label">
                                Business Name
                            </label>

                            <input
                                type="text"
                                name="business_name"
                                id="business_name"
                                value="{{ old('business_name') }}"
                                class="form-control @error('business_name') is-invalid @enderror"
                            >

                            @error('business_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="business_postal_address" class="form-label">
                                Business Postal Address
                            </label>

                            <input
                                type="text"
                                name="business_postal_address"
                                id="business_postal_address"
                                value="{{ old('business_postal_address') }}"
                                class="form-control @error('business_postal_address') is-invalid @enderror"
                            >

                            @error('business_postal_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <!-- Financial Position -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">5</span>
                        Financial Position
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label for="total_share_contribution" class="form-label">
                                Total Share Contribution
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="total_share_contribution"
                                    id="total_share_contribution"
                                    value="{{ old('total_share_contribution', $member->total_share_contribution ?? 0) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('total_share_contribution') is-invalid @enderror"
                                >

                                @error('total_share_contribution')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="outstanding_loan_balance" class="form-label">
                                Outstanding Loan Balance
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="outstanding_loan_balance"
                                    id="outstanding_loan_balance"
                                    value="{{ old('outstanding_loan_balance', $member->outstanding_loan_balance ?? 0) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('outstanding_loan_balance') is-invalid @enderror"
                                >

                                @error('outstanding_loan_balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="monthly_share_contribution" class="form-label">
                                Monthly Share Contribution
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="monthly_share_contribution"
                                    id="monthly_share_contribution"
                                    value="{{ old('monthly_share_contribution', $member->monthly_share_contribution ?? 0) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('monthly_share_contribution') is-invalid @enderror"
                                >

                                @error('monthly_share_contribution')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Loan Security -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">6</span>
                        Loan Security
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="security_shares" class="form-label">
                                Security Provided by Member Shares
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="security_shares"
                                    id="security_shares"
                                    value="{{ old('security_shares', 0) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control security-field @error('security_shares') is-invalid @enderror"
                                >

                                @error('security_shares')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="guarantor_security" class="form-label">
                                Security Provided by Guarantors
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">KES</span>

                                <input
                                    type="number"
                                    name="guarantor_security"
                                    id="guarantor_security"
                                    value="{{ old('guarantor_security', 0) }}"
                                    min="0"
                                    step="0.01"
                                    class="form-control security-field @error('guarantor_security') is-invalid @enderror"
                                >

                                @error('guarantor_security')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div id="securitySummary" class="alert alert-light border mb-0">
                                <div class="row g-3">

                                    <div class="col-md-4">
                                        <span class="text-muted d-block">Total Security</span>
                                        <span class="summary-value">
                                            KES <span id="totalSecurity">0.00</span>
                                        </span>
                                    </div>

                                    <div class="col-md-4">
                                        <span class="text-muted d-block">Amount Requested</span>
                                        <span class="summary-value">
                                            KES <span id="requestedAmountSummary">0.00</span>
                                        </span>
                                    </div>

                                    <div class="col-md-4">
                                        <span class="text-muted d-block">Security Difference</span>
                                        <span class="summary-value">
                                            KES <span id="securityDifference">0.00</span>
                                        </span>
                                    </div>

                                </div>

                                <small id="securityMessage" class="d-block mt-3 text-muted">
                                    Enter the security amounts to compare them with the requested loan.
                                </small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Declaration -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">7</span>
                        Applicant Declaration
                    </h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        I declare that the information provided in this loan application is accurate and complete.
                        I authorize the institution to verify the information and process the application according
                        to its lending policies.
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="declaration_date" class="form-label required-label">
                                Declaration Date
                            </label>

                            <input
                                type="date"
                                name="declaration_date"
                                id="declaration_date"
                                value="{{ old('declaration_date', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}"
                                class="form-control @error('declaration_date') is-invalid @enderror"
                                required
                            >

                            @error('declaration_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="applicant_signature" class="form-label required-label">
                                Applicant Signature
                            </label>

                            <input
                                type="file"
                                name="applicant_signature"
                                id="applicant_signature"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="form-control @error('applicant_signature') is-invalid @enderror"
                                required
                            >

                            <img
                                id="signaturePreview"
                                class="signature-preview"
                                alt="Applicant signature preview"
                            >

                            @error('applicant_signature')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check border rounded bg-light p-3 ps-5">
                                <input
                                    type="checkbox"
                                    name="confirmed_declaration"
                                    id="confirmed_declaration"
                                    value="1"
                                    class="form-check-input"
                                    @checked(old('confirmed_declaration'))
                                    required
                                >

                                <label
                                    for="confirmed_declaration"
                                    class="form-check-label required-label"
                                >
                                    I confirm that I have read and accepted the loan application declaration.
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Status is controlled by the application workflow. -->

            <div class="submit-section">
                <div class="d-flex flex-column flex-md-row justify-content-end gap-2">

                    <button
                        type="submit"
                        name="submission_action"
                        value="draft"
                        class="btn btn-outline-secondary px-4"
                    >
                        Save as Draft
                    </button>

                    <button
                        type="submit"
                        name="submission_action"
                        value="submit"
                        id="submitButton"
                        class="btn btn-success px-5"
                    >
                        Submit Application
                    </button>

                </div>
            </div>

        </form>
    </div>    
@stop

@section('script')
<script>
    $(document).ready(function () {
        const maximumFileSize = 5 * 1024 * 1024;

        function toggleSection(toggleSelector, sectionSelector) {
            if ($(toggleSelector).is(':checked')) {
                $(sectionSelector).stop(true, true).slideDown();
            } else {
                $(sectionSelector).stop(true, true).slideUp();
            }
        }

        function calculateInstallment() {
            const amount = parseFloat($('#amount_requested').val()) || 0;
            const months = parseInt($('#repayment_period_months').val(), 10) || 0;

            let installment = 0;

            if (amount > 0 && months > 0) {
                installment = amount / months;
            }

            $('#monthly_installment').val(installment.toFixed(2));
        }

        function updateSecuritySummary() {
            const requestedAmount =
                parseFloat($('#amount_requested').val()) || 0;

            const shareSecurity =
                parseFloat($('#security_shares').val()) || 0;

            const guarantorSecurity =
                parseFloat($('#guarantor_security').val()) || 0;

            const totalSecurity = shareSecurity + guarantorSecurity;
            const difference = totalSecurity - requestedAmount;

            $('#totalSecurity').text(totalSecurity.toFixed(2));
            $('#requestedAmountSummary').text(requestedAmount.toFixed(2));
            $('#securityDifference').text(difference.toFixed(2));

            const summary = $('#securitySummary');
            const message = $('#securityMessage');

            summary.removeClass(
                'alert-light alert-success alert-warning alert-danger'
            );

            if (requestedAmount <= 0) {
                summary.addClass('alert-light');

                message.text(
                    'Enter the requested loan amount to evaluate the security.'
                );
            } else if (totalSecurity >= requestedAmount) {
                summary.addClass('alert-success');

                message.text(
                    'The total declared security covers the requested loan amount.'
                );
            } else if (totalSecurity > 0) {
                summary.addClass('alert-warning');

                message.text(
                    'The declared security is below the requested loan amount.'
                );
            } else {
                summary.addClass('alert-danger');

                message.text(
                    'No loan security has been declared.'
                );
            }
        }

        function numberToWords(number) {
            const ones = [
                '',
                'one',
                'two',
                'three',
                'four',
                'five',
                'six',
                'seven',
                'eight',
                'nine',
                'ten',
                'eleven',
                'twelve',
                'thirteen',
                'fourteen',
                'fifteen',
                'sixteen',
                'seventeen',
                'eighteen',
                'nineteen'
            ];

            const tens = [
                '',
                '',
                'twenty',
                'thirty',
                'forty',
                'fifty',
                'sixty',
                'seventy',
                'eighty',
                'ninety'
            ];

            function convertBelowThousand(value) {
                let words = '';

                if (value >= 100) {
                    words += ones[Math.floor(value / 100)] + ' hundred';
                    value %= 100;

                    if (value > 0) {
                        words += ' and ';
                    }
                }

                if (value >= 20) {
                    words += tens[Math.floor(value / 10)];
                    value %= 10;

                    if (value > 0) {
                        words += '-' + ones[value];
                    }
                } else if (value > 0) {
                    words += ones[value];
                }

                return words;
            }

            const parsedNumber = parseFloat(number);

            if (!parsedNumber || parsedNumber < 0) {
                return '';
            }

            const wholeNumber = Math.floor(parsedNumber);
            const cents = Math.round((parsedNumber - wholeNumber) * 100);

            if (wholeNumber === 0) {
                return 'Zero Kenya shillings only';
            }

            const groups = [
                { value: 1000000000000, name: 'trillion' },
                { value: 1000000000, name: 'billion' },
                { value: 1000000, name: 'million' },
                { value: 1000, name: 'thousand' },
                { value: 1, name: '' }
            ];

            let remaining = wholeNumber;
            let words = [];

            groups.forEach(function (group) {
                if (remaining >= group.value) {
                    const groupValue = Math.floor(
                        remaining / group.value
                    );

                    words.push(
                        convertBelowThousand(groupValue) +
                        (group.name ? ' ' + group.name : '')
                    );

                    remaining %= group.value;
                }
            });

            let result =
                words.join(' ') + ' Kenya shillings';

            if (cents > 0) {
                result +=
                    ' and ' +
                    convertBelowThousand(cents) +
                    ' cents';
            }

            return result.charAt(0).toUpperCase() +
                result.slice(1) +
                ' only';
        }

        function updateAmountInWords() {
            const amount = $('#amount_requested').val();

            $('#amount_in_words').val(
                numberToWords(amount)
            );
        }

        toggleSection('#isEmployed', '#employmentSection');
        toggleSection('#ownsBusiness', '#businessSection');

        $('#isEmployed').on('change', function () {
            toggleSection('#isEmployed', '#employmentSection');

            if (!$(this).is(':checked')) {
                $('#employmentSection')
                    .find('input, select')
                    .val('');
            }
        });

        $('#ownsBusiness').on('change', function () {
            toggleSection('#ownsBusiness', '#businessSection');

            if (!$(this).is(':checked')) {
                $('#businessSection')
                    .find('input')
                    .val('');
            }
        });

        $('#employment_type').on('change', function () {
            if ($(this).val() === 'self_employed') {
                $('#ownsBusiness').prop('checked', true);
                toggleSection('#ownsBusiness', '#businessSection');
            }
        });

        $('#amount_requested, #repayment_period_months').on(
            'input change',
            function () {
                calculateInstallment();
                updateSecuritySummary();
            }
        );

        $('#amount_requested').on('input change', function () {
            updateAmountInWords();

            if (!$('#purpose_amount').val()) {
                $('#purpose_amount').val($(this).val());
            }
        });

        $('.security-field').on('input change', function () {
            updateSecuritySummary();
        });

        $('#applicant_signature').on('change', function () {
            const file = this.files[0];

            $(this).removeClass('is-invalid');
            $('.client-signature-error').remove();

            if (!file) {
                $('#signaturePreview')
                    .hide()
                    .attr('src', '');

                return;
            }

            if (file.size > maximumFileSize) {
                $(this)
                    .val('')
                    .addClass('is-invalid');

                $('<div class="invalid-feedback client-signature-error d-block">' +
                    'The signature file must not exceed 5 MB.' +
                    '</div>').insertAfter(this);

                $('#signaturePreview')
                    .hide()
                    .attr('src', '');

                return;
            }

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    $('#signaturePreview')
                        .attr('src', event.target.result)
                        .fadeIn();
                };

                reader.readAsDataURL(file);
            } else {
                $('#signaturePreview')
                    .hide()
                    .attr('src', '');
            }
        });

        $('#loanApplicationForm').on('submit', function (event) {
            const form = this;
            const action = $(document.activeElement)
                .val();

            /*
             * Draft records may be saved without completing every
             * required field. Submitted records must pass validation.
             */
            if (action === 'draft') {
                $('#submitButton').prop('disabled', true);
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                const firstInvalidField =
                    $(form).find(':invalid').first();

                if (firstInvalidField.length) {
                    $('html, body').animate({
                        scrollTop:
                            firstInvalidField.offset().top - 120
                    }, 400);

                    firstInvalidField.trigger('focus');
                }
            } else {
                $('#submitButton')
                    .prop('disabled', true)
                    .html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>' +
                        'Submitting...'
                    );
            }

            $(form).addClass('was-validated');
        });

        calculateInstallment();
        updateAmountInWords();
        updateSecuritySummary();
    });
</script>
@endsection

{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Loan Application Form</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    
</head>

<body>


<!-- jQuery 3.6 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html> --}}
