@extends('layouts.core')

@section('title', 'Loan Applications')

@section('content')
    @include('loan_applications.partial.header')

    @php 
        $applicationNumber = 0;
        $members = collect();
        $loanProducts = collect();
    @endphp

    <!-- Loan Details -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Loan Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Application No.
                    </label>
                    <input type="text"
                           class="form-control"
                           value="{{ $application->application_number ?? $applicationNumber }}"
                           readonly>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">
                        Member
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select"
                            name="member_id"
                            required>
                        <option value="">Select Member</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}"
                                {{ old('member_id',$application->member_id ?? '') == $member->id ? 'selected' : '' }}>
                                {{ $member->member_number }}
                                -
                                {{ $member->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Loan Product
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select"
                            name="loan_product_id"
                            required>
                        <option value="">Select Product</option>
                        @foreach($loanProducts as $product)
                            <option value="{{ $product->id }}"
                                {{ old('loan_product_id',$application->loan_product_id ?? '')==$product->id?'selected':'' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">
                        Amount Requested
                    </label>
                    <input type="number"
                           step="0.01"
                           name="amount_requested"
                           class="form-control text-end">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">
                        Amount In Words
                    </label>
                    <textarea class="form-control"
                              rows="2"
                              name="amount_in_words"></textarea>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">
                        Repayment (Months)
                    </label>
                    <input type="number"
                           class="form-control"
                           name="repayment_period_months">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">
                        Monthly Installment
                    </label>
                    <input type="number"
                           step="0.01"
                           class="form-control text-end"
                           name="monthly_installment"
                           readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">
                        Required Date
                    </label>
                    <input type="date"
                           class="form-control"
                           name="required_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        Payment Mode
                    </label>
                    <select class="form-select"
                            name="payment_mode">
                        <option value="standing_order">
                            Standing Order
                        </option>
                        <option value="check_off">
                            Check Off
                        </option>
                        <option value="post_dated_cheques">
                            Post Dated Cheques
                        </option>
                        <option value="cash">
                            Cash
                        </option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        Loan Purpose
                    </label>
                    <textarea class="form-control"
                              rows="2"
                              name="loan_purpose"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Employment / Business -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                Employment / Business Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Employer Name
                    </label>
                    <input class="form-control"
                           name="employer_name">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Employment Type
                    </label>
                    <select class="form-select"
                            name="employment_type">
                        <option></option>
                        <option value="permanent">Permanent</option>
                        <option value="contract">Contract</option>
                        <option value="seasonal">Seasonal</option>
                        <option value="self_employed">Self Employed</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        Work Station
                    </label>
                    <input class="form-control"
                           name="work_station">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">
                        Employer Postal Address
                    </label>
                    <input class="form-control"
                           name="employer_postal_address">
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        Business Name
                    </label>
                    <input class="form-control"
                           name="business_name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">
                        Business Postal Address
                    </label>
                    <input class="form-control"
                           name="business_postal_address">
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Position -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0">
                Financial Position
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Total Share Contribution</label>
                    <input class="form-control text-end"
                           readonly
                           name="total_share_contribution">
                </div>
                <div class="col-md-4">
                    <label>Outstanding Loan Balance</label>
                    <input class="form-control text-end"
                           readonly
                           name="outstanding_loan_balance">
                </div>
                <div class="col-md-4">
                    <label>Monthly Share Contribution</label>
                    <input class="form-control text-end"
                           readonly
                           name="monthly_share_contribution">
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Security -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                Loan Security
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label>Shares Available as Security</label>
                    <input class="form-control text-end"
                           readonly
                           name="security_shares">
                </div>
                <div class="col-md-6">
                    <label>Guarantor Security</label>
                    <input class="form-control text-end"
                           readonly
                           name="guarantor_security">
                </div>
            </div>
        </div>
    </div>

    <!-- Declaration -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                Declaration
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label>
                        Applicant Signature
                    </label>
                    <input type="file"
                           class="form-control"
                           name="applicant_signature">
                </div>
                <div class="col-md-3">
                    <label>
                        Declaration Date
                    </label>
                    <input type="date"
                           class="form-control"
                           name="declaration_date">
                </div>
                <div class="col-md-3">
                    <label>
                        Status
                    </label>
                    <input class="form-control"
                           readonly
                           value="{{ $application->status ?? 'Draft' }}">
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-secondary"
                    name="status"
                    value="draft">
                Save Draft
            </button>
            <button class="btn btn-primary"
                    name="status"
                    value="submitted">
                Submit Application
            </button>
        </div>
    </div>
@stop

@section('script')
<script>
    
</script>
@endsection

