@extends('layouts.core')
@section('title', 'Loan Applications | View')
    
@section('content')
@include('loan_applications.partial.header')
<div class="container-fluid">

    @php
        $statusClasses = [
            'draft' => 'secondary',
            'submitted' => 'primary',
            'under_review' => 'warning',
            'approved' => 'success',
            'deferred' => 'warning',
            'rejected' => 'danger',
            'disbursed' => 'info',
            'closed' => 'dark',
        ];

        $statusClass = $statusClasses[$application->status] ?? 'secondary';
    @endphp

    {{-- HEADER --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                <div>
                    <h4 class="mb-1">
                        Loan Application {{ $application->application_number }}
                    </h4>

                    <div class="text-muted">
                        {{ $application->member->full_name ?? '-' }}
                        @if(optional($application->member)->membership_number)
                            · {{ $application->member->membership_number }}
                        @endif
                    </div>
                </div>

                <div class="text-end">
                    <span class="badge bg-{{ $statusClass }} fs-6">
                        {{ ucwords(str_replace('_', ' ', $application->status)) }}
                    </span>

                    <div class="mt-2">
                        <a href="{{ route('loan_applications.index') }}"
                           class="btn btn-sm btn-light border">
                            Back
                        </a>

                        @if($application->status === 'draft')
                            <a href="{{ route('loan_applications.edit', $application->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                Edit
                            </a>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>


    <div class="row">

        {{-- LEFT MAIN CONTENT --}}
        <div class="col-lg-9">

            {{-- =================================================== --}}
            {{-- 1. LOAN DETAILS --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Loan Details</h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Loan Product
                            </small>

                            <strong>
                                {{ optional($application->loanProduct)->code }}
                                -
                                {{ optional($application->loanProduct)->name ?? '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Amount Requested
                            </small>

                            <strong>
                                KES {{ number_format($application->amount_requested, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Repayment Period
                            </small>

                            <strong>
                                {{ $application->repayment_period_months }} Months
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Monthly Installment
                            </small>

                            <strong>
                                KES {{ number_format($application->monthly_installment, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Required Date
                            </small>

                            <strong>
                                {{ $application->required_date ? \Carbon\Carbon::parse($application->required_date)->format('d M Y') : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Payment Mode
                            </small>

                            <strong>
                                {{ ucwords(str_replace('_', ' ', $application->payment_mode)) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Purpose Amount
                            </small>

                            <strong>
                                {{ $application->purpose_amount !== null ? 'KES '.number_format($application->purpose_amount, 2) : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-8 mb-3">
                            <small class="text-muted d-block">
                                Amount in Words
                            </small>

                            <strong>
                                {{ $application->amount_in_words ?: '-' }}
                            </strong>
                        </div>

                        <div class="col-12">
                            <small class="text-muted d-block">
                                Loan Purpose
                            </small>

                            <div class="border rounded bg-light p-3">
                                {{ $application->loan_purpose ?: '-' }}
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 2. MEMBER --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Member Information</h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Member
                            </small>

                            <strong>
                                {{ optional($application->member)->full_name ?? '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Membership Number
                            </small>

                            <strong>
                                {{ optional($application->member)->membership_number ?? '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                National ID
                            </small>

                            <strong>
                                {{ optional($application->member)->national_id ?? '-' }}
                            </strong>
                        </div>

                    </div>

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 3. EMPLOYMENT / BUSINESS --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Employment & Business Information</h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Employment Type
                            </small>

                            <strong>
                                {{ $application->employment_type ? ucwords(str_replace('_', ' ', $application->employment_type)) : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Employer
                            </small>

                            <strong>
                                {{ $application->employer_name ?: '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Work Station
                            </small>

                            <strong>
                                {{ $application->work_station ?: '-' }}
                            </strong>
                        </div>

                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">
                                Employer Postal Address
                            </small>

                            <strong>
                                {{ $application->employer_postal_address ?: '-' }}
                            </strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">
                                Business Name
                            </small>

                            <strong>
                                {{ $application->business_name ?: '-' }}
                            </strong>
                        </div>

                        <div class="col-md-3 mb-3">
                            <small class="text-muted d-block">
                                Business Postal Address
                            </small>

                            <strong>
                                {{ $application->business_postal_address ?: '-' }}
                            </strong>
                        </div>

                    </div>

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 4. FINANCIAL POSITION --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Financial Position</h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Total Share Contribution
                            </small>

                            <strong>
                                KES {{ number_format($application->total_share_contribution, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Outstanding Loan Balance
                            </small>

                            <strong>
                                KES {{ number_format($application->outstanding_loan_balance, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Monthly Share Contribution
                            </small>

                            <strong>
                                KES {{ number_format($application->monthly_share_contribution, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Security Shares
                            </small>

                            <strong>
                                KES {{ number_format($application->security_shares, 2) }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">
                                Guarantor Security
                            </small>

                            <strong>
                                KES {{ number_format($application->guarantor_security, 2) }}
                            </strong>
                        </div>

                    </div>

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 5. GUARANTORS --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        Loan Guarantors
                    </h5>

                    <span class="badge bg-secondary">
                        {{ $application->guarantors->count() }}
                    </span>

                </div>

                <div class="card-body p-0">

                    @if($application->guarantors->count())

                        <div class="table-responsive">

                            <table class="table table-hover mb-0">

                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Guarantor</th>
                                        <th>Member No.</th>
                                        <th>National ID</th>
                                        <th class="text-end">Shares Offered</th>
                                        <th>Witness</th>
                                        <th>Signature</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach($application->guarantors as $guarantor)

                                        <tr>

                                            <td>
                                                {{ $loop->iteration }}
                                            </td>

                                            <td>
                                                <strong>
                                                    {{ $guarantor->guarantor_name }}
                                                </strong>
                                            </td>

                                            <td>
                                                {{ $guarantor->member_number }}
                                            </td>

                                            <td>
                                                {{ $guarantor->national_id }}
                                            </td>

                                            <td class="text-end">
                                                KES {{ number_format($guarantor->shares_offered, 2) }}
                                            </td>

                                            <td>
                                                {{ $guarantor->witness_name ?: '-' }}
                                            </td>

                                            <td>
                                                @if($guarantor->signature)

                                                    <a href="{{ Storage::url($guarantor->signature) }}"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">

                                                        View

                                                    </a>

                                                @else
                                                    -
                                                @endif
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                                <tfoot>

                                    <tr class="table-light">

                                        <th colspan="4">
                                            Total Guarantor Security
                                        </th>

                                        <th class="text-end">
                                            KES {{ number_format($application->guarantors->sum('shares_offered'), 2) }}
                                        </th>

                                        <th colspan="2"></th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    @else

                        <div class="p-4 text-muted text-center">
                            No guarantors have been recorded.
                        </div>

                    @endif

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 6. SECURITIES --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        Security / Collateral
                    </h5>

                    <span class="badge bg-secondary">
                        {{ $application->securities->count() }}
                    </span>

                </div>

                <div class="card-body">

                    @forelse($application->securities as $security)

                        <div class="border rounded p-3 mb-3">

                            <div class="d-flex justify-content-between align-items-start mb-3">

                                <div>

                                    <strong>
                                        {{ $security->security_name }}
                                    </strong>

                                    <div class="text-muted small">
                                        {{ ucwords(str_replace('_', ' ', $security->security_type)) }}
                                    </div>

                                </div>

                                @if($security->status === 'verified')
                                    <span class="badge bg-success">Verified</span>

                                @elseif($security->status === 'pledged')
                                    <span class="badge bg-primary">Pledged</span>

                                @elseif($security->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>

                                @elseif($security->status === 'released')
                                    <span class="badge bg-dark">Released</span>

                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif

                            </div>

                            <div class="row">

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Security Value
                                    </small>

                                    <strong>
                                        KES {{ number_format($security->security_value, 2) }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Accepted Value
                                    </small>

                                    <strong>
                                        {{ $security->accepted_value !== null ? 'KES '.number_format($security->accepted_value, 2) : '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Reference
                                    </small>

                                    <strong>
                                        {{ $security->reference_number ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Verified
                                    </small>

                                    @if($security->is_verified)
                                        <span class="badge bg-success">
                                            Yes
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            No
                                        </span>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">
                                        Description
                                    </small>

                                    <div>
                                        {{ $security->description ?: '-' }}
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Owner
                                    </small>

                                    <strong>
                                        {{ $security->owner_name ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Owner National ID
                                    </small>

                                    <strong>
                                        {{ $security->owner_national_id ?: '-' }}
                                    </strong>
                                </div>

                                @if($security->verified_at)

                                    <div class="col-md-3 mb-3">
                                        <small class="text-muted d-block">
                                            Verified At
                                        </small>

                                        <strong>
                                            {{ \Carbon\Carbon::parse($security->verified_at)->format('d M Y H:i') }}
                                        </strong>
                                    </div>

                                @endif

                                @if($security->pledged_date)

                                    <div class="col-md-3 mb-3">
                                        <small class="text-muted d-block">
                                            Pledged Date
                                        </small>

                                        <strong>
                                            {{ \Carbon\Carbon::parse($security->pledged_date)->format('d M Y') }}
                                        </strong>
                                    </div>

                                @endif

                                @if($security->released_date)

                                    <div class="col-md-3 mb-3">
                                        <small class="text-muted d-block">
                                            Released Date
                                        </small>

                                        <strong>
                                            {{ \Carbon\Carbon::parse($security->released_date)->format('d M Y') }}
                                        </strong>
                                    </div>

                                @endif

                                <div class="col-md-3 mb-3">

                                    <small class="text-muted d-block">
                                        Document
                                    </small>

                                    @if($security->supporting_document)

                                        <a href="{{ Storage::url($security->supporting_document) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">

                                            View Document

                                        </a>

                                    @else
                                        -
                                    @endif

                                </div>

                                @if($security->remarks)

                                    <div class="col-12">

                                        <small class="text-muted d-block">
                                            Remarks
                                        </small>

                                        <div class="border rounded bg-light p-2">
                                            {{ $security->remarks }}
                                        </div>

                                    </div>

                                @endif

                            </div>

                        </div>

                    @empty

                        <div class="text-muted text-center py-3">
                            No securities have been recorded.
                        </div>

                    @endforelse

                </div>
            </div>


            {{-- =================================================== --}}
            {{-- 7. WITNESSES --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header d-flex justify-content-between">

                    <h5 class="mb-0">
                        Witnesses
                    </h5>

                    <span class="badge bg-secondary">
                        {{ $application->witnesses->count() }}
                    </span>

                </div>

                <div class="card-body">

                    @forelse($application->witnesses as $witness)

                        <div class="border rounded p-3 mb-3">

                            <div class="row">

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        Name
                                    </small>

                                    <strong>
                                        {{ $witness->name }}
                                    </strong>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        National ID
                                    </small>

                                    <strong>
                                        {{ $witness->national_id }}
                                    </strong>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        Payroll Number
                                    </small>

                                    <strong>
                                        {{ $witness->payroll_number ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        Employer
                                    </small>

                                    <strong>
                                        {{ $witness->employer ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        Station
                                    </small>

                                    <strong>
                                        {{ $witness->station ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <small class="text-muted d-block">
                                        Phone
                                    </small>

                                    <strong>
                                        {{ $witness->phone ?: '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">
                                        Address
                                    </small>

                                    <div>
                                        {{ $witness->address ?: '-' }}
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Signed Date
                                    </small>

                                    <strong>
                                        {{ $witness->signed_at ? \Carbon\Carbon::parse($witness->signed_at)->format('d M Y') : '-' }}
                                    </strong>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <small class="text-muted d-block">
                                        Signature
                                    </small>

                                    @if($witness->signature)

                                        <a href="{{ Storage::url($witness->signature) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           target="_blank">

                                            View Signature

                                        </a>

                                    @else
                                        -
                                    @endif

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="text-muted text-center py-3">
                            No witnesses have been recorded.
                        </div>

                    @endforelse

                </div>

            </div>


            {{-- =================================================== --}}
            {{-- 8. DECLARATION --}}
            {{-- =================================================== --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h5 class="mb-0">
                        Applicant Declaration
                    </h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <small class="text-muted d-block">
                                Declaration Date
                            </small>

                            <strong>
                                {{ $application->declaration_date ? \Carbon\Carbon::parse($application->declaration_date)->format('d M Y') : '-' }}
                            </strong>

                        </div>

                        <div class="col-md-4 mb-3">

                            <small class="text-muted d-block">
                                Applicant Signature
                            </small>

                            @if($application->applicant_signature)

                                <a href="{{ Storage::url($application->applicant_signature) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   target="_blank">

                                    View Signature

                                </a>

                            @else
                                -
                            @endif

                        </div>

                    </div>

                </div>

            </div>


            {{-- NOTES --}}
            @if($application->defer_note || $application->rejection_note)

                <div class="card shadow-sm mb-4">

                    <div class="card-header">
                        <h5 class="mb-0">
                            Decision Notes
                        </h5>
                    </div>

                    <div class="card-body">

                        @if($application->defer_note)

                            <div class="alert alert-warning">
                                <strong>Deferral Note</strong>

                                <div class="mt-1">
                                    {{ $application->defer_note }}
                                </div>
                            </div>

                        @endif

                        @if($application->rejection_note)

                            <div class="alert alert-danger mb-0">
                                <strong>Rejection Note</strong>

                                <div class="mt-1">
                                    {{ $application->rejection_note }}
                                </div>
                            </div>

                        @endif

                    </div>

                </div>

            @endif

        </div>


        {{-- ======================================================= --}}
        {{-- RIGHT SIDEBAR --}}
        {{-- ======================================================= --}}

        <div class="col-lg-3">

            {{-- APPLICATION SUMMARY --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h6 class="mb-0">
                        Application Summary
                    </h6>
                </div>

                <div class="card-body">

                    <small class="text-muted d-block">
                        Application Number
                    </small>

                    <strong>
                        {{ $application->application_number }}
                    </strong>

                    <hr>

                    <small class="text-muted d-block">
                        Amount Requested
                    </small>

                    <h5 class="mb-0">
                        KES {{ number_format($application->amount_requested, 2) }}
                    </h5>

                    <hr>

                    <small class="text-muted d-block">
                        Monthly Installment
                    </small>

                    <strong>
                        KES {{ number_format($application->monthly_installment, 2) }}
                    </strong>

                    <hr>

                    <small class="text-muted d-block">
                        Guarantor Security
                    </small>

                    <strong>
                        KES {{ number_format($application->guarantors->sum('shares_offered'), 2) }}
                    </strong>

                    @if(optional($application->loanProduct)->requires_guarantors)

                        @php
                            $requiredCoverage =
                                $application->amount_requested *
                                (($application->loanProduct->minimum_guarantor_coverage_percentage ?? 0) / 100);

                            $actualCoverage =
                                $application->amount_requested > 0
                                ? ($application->guarantors->sum('shares_offered') / $application->amount_requested) * 100
                                : 0;
                        @endphp

                        <div class="mt-2">

                            <small class="text-muted">
                                Required:
                                {{ number_format($application->loanProduct->minimum_guarantor_coverage_percentage, 2) }}%
                            </small>

                            <div class="progress mt-1" style="height:8px;">
                                <div class="progress-bar"
                                     role="progressbar"
                                     style="width:{{ min($actualCoverage, 100) }}%">
                                </div>
                            </div>

                            <small class="{{ $actualCoverage >= $application->loanProduct->minimum_guarantor_coverage_percentage ? 'text-success' : 'text-danger' }}">
                                {{ number_format($actualCoverage, 2) }}% covered
                            </small>

                        </div>

                    @endif

                </div>

            </div>


            {{-- PRODUCT RULES --}}

            @if($application->loanProduct)

                <div class="card shadow-sm mb-4">

                    <div class="card-header">
                        <h6 class="mb-0">
                            Product Rules
                        </h6>
                    </div>

                    <div class="card-body">

                        <small class="text-muted d-block">
                            Amount Range
                        </small>

                        <strong>
                            KES {{ number_format($application->loanProduct->minimum_amount, 2) }}
                            -

                            {{ $application->loanProduct->maximum_amount !== null
                                ? 'KES '.number_format($application->loanProduct->maximum_amount, 2)
                                : 'No Maximum' }}
                        </strong>

                        <hr>

                        <small class="text-muted d-block">
                            Repayment Period
                        </small>

                        <strong>
                            {{ $application->loanProduct->minimum_repayment_months }}
                            -
                            {{ $application->loanProduct->maximum_repayment_months }}
                            Months
                        </strong>

                        <hr>

                        <small class="text-muted d-block">
                            Interest
                        </small>

                        <strong>
                            {{ number_format($application->loanProduct->interest_rate, 4) }}%
                        </strong>

                        <div class="small text-muted">
                            {{ ucwords(str_replace('_', ' ', $application->loanProduct->interest_method)) }}
                            /
                            {{ ucwords(str_replace('_', ' ', $application->loanProduct->interest_frequency)) }}
                        </div>

                        <hr>

                        <small class="text-muted d-block">
                            Guarantors
                        </small>

                        @if($application->loanProduct->requires_guarantors)

                            <strong>
                                Min {{ $application->loanProduct->minimum_guarantors }}

                                @if($application->loanProduct->maximum_guarantors)
                                    / Max {{ $application->loanProduct->maximum_guarantors }}
                                @endif
                            </strong>

                            <div class="small text-muted">
                                {{ number_format($application->loanProduct->minimum_guarantor_coverage_percentage, 2) }}%
                                coverage required
                            </div>

                        @else

                            <strong>
                                Not Required
                            </strong>

                        @endif

                    </div>

                </div>

            @endif


            {{-- WORKFLOW --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h6 class="mb-0">
                        Workflow
                    </h6>
                </div>

                <div class="card-body">

                    @if($application->drafted_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Drafted
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->drafted_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->draftedBy)
                                <div class="small">
                                    {{ $application->draftedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->submitted_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Submitted
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->submitted_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->submittedBy)
                                <div class="small">
                                    {{ $application->submittedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->reviewed_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Reviewed
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->reviewed_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->reviewedBy)
                                <div class="small">
                                    {{ $application->reviewedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->approved_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Approved
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->approved_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->approvedBy)
                                <div class="small">
                                    {{ $application->approvedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->deferred_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Deferred
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->deferred_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->deferredBy)
                                <div class="small">
                                    {{ $application->deferredBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->rejected_at)

                        <div class="mb-3">

                            <small class="text-muted d-block">
                                Rejected
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->rejected_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->rejectedBy)
                                <div class="small">
                                    {{ $application->rejectedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif


                    @if($application->closed_at)

                        <div>

                            <small class="text-muted d-block">
                                Closed
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($application->closed_at)->format('d M Y H:i') }}
                            </strong>

                            @if($application->closedBy)
                                <div class="small">
                                    {{ $application->closedBy->name }}
                                </div>
                            @endif

                        </div>

                    @endif

                </div>

            </div>


            {{-- ACTIONS --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h6 class="mb-0">
                        Actions
                    </h6>
                </div>

                <div class="card-body d-grid gap-2">

                    @if($application->status === 'draft')

                        <a href="{{ route('loan_applications.edit', $application->id) }}"
                           class="btn btn-outline-primary">

                            Edit Application

                        </a>

                        <button type="button"
                                class="btn btn-primary workflow-action"
                                data-action="submit">

                            Submit Application

                        </button>

                    @endif


                    @if($application->status === 'submitted')

                        <button type="button"
                                class="btn btn-warning workflow-action"
                                data-action="review">

                            Start Review

                        </button>

                    @endif


                    @if(in_array($application->status, ['submitted','under_review','deferred']))

                        <button type="button"
                                class="btn btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#approvalModal">

                            Approve

                        </button>

                        <button type="button"
                                class="btn btn-outline-warning workflow-action"
                                data-action="defer"
                                data-note="1">

                            Defer

                        </button>

                        <button type="button"
                                class="btn btn-outline-danger workflow-action"
                                data-action="reject"
                                data-note="1">

                            Reject

                        </button>

                    @endif


                    @if($application->status === 'approved')

                        <a href="{{ route('loan-disbursements.create', ['loan_application_id' => $application->id]) }}"
                           class="btn btn-primary">

                            Proceed to Disbursement

                        </a>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ============================================================= --}}
{{-- APPROVAL MODAL --}}
{{-- ============================================================= --}}

<div class="modal fade"
     id="approvalModal"
     tabindex="-1">

    <div class="modal-dialog">

        <form method="POST"
              action="{{ route('loan_applications.approve', $application) }}">

            @csrf
            @method('PATCH')

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Approve Loan Application
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Amount Requested
                        </label>

                        <div class="form-control bg-light">
                            KES {{ number_format($application->amount_requested, 2) }}
                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Approved Amount
                            <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                KES
                            </span>

                            <input type="number"
                                   name="approved_amount"
                                   class="form-control"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $application->amount_requested }}"
                                   value="{{ $application->amount_requested }}"
                                   required>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Repayment Period
                        </label>

                        <div class="input-group">

                            <input type="number"
                                   name="repayment_period_months"
                                   class="form-control"
                                   value="{{ $application->repayment_period_months }}"
                                   required>

                            <span class="input-group-text">
                                Months
                            </span>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Approval Note
                        </label>

                        <textarea name="approval_note"
                                  class="form-control"
                                  rows="3"></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-success">

                        Approve Loan

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- ============================================================= --}}
{{-- WORKFLOW MODAL --}}
{{-- ============================================================= --}}

<div class="modal fade"
     id="workflowModal"
     tabindex="-1">

    <div class="modal-dialog">

        <form method="POST"
              action="{{ route('loan_applications.workflow', $application->id) }}"
              id="workflowForm">

            @csrf
            @method('PATCH')

            <input type="hidden"
                   name="action"
                   id="workflowAction">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title"
                        id="workflowTitle">

                        Update Application

                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <p id="workflowMessage"
                       class="mb-3">
                    </p>

                    <div id="workflowNoteArea"
                         class="d-none">

                        <label class="form-label">
                            Reason / Note
                        </label>

                        <textarea name="note"
                                  class="form-control"
                                  rows="4"></textarea>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="workflowSubmit">

                        Continue

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>
@endsection


@section('script')
<script>
$(function () {

    $('.workflow-action').on('click', function () {

        const action = $(this).data('action');
        const requiresNote = $(this).data('note') == 1;

        let title = 'Update Application';
        let message = 'Are you sure you want to continue?';
        let buttonText = 'Continue';
        let buttonClass = 'btn-primary';

        if (action === 'submit') {

            title = 'Submit Application';
            message = 'Submit this loan application for review?';
            buttonText = 'Submit Application';

        }

        if (action === 'review') {

            title = 'Start Loan Review';
            message = 'Move this loan application to under review?';
            buttonText = 'Start Review';
            buttonClass = 'btn-warning';

        }

        if (action === 'defer') {

            title = 'Defer Loan Application';
            message = 'Provide the reason why this application is being deferred.';
            buttonText = 'Defer Application';
            buttonClass = 'btn-warning';

        }

        if (action === 'reject') {

            title = 'Reject Loan Application';
            message = 'Provide the reason why this loan application is being rejected.';
            buttonText = 'Reject Application';
            buttonClass = 'btn-danger';

        }

        $('#workflowAction').val(action);

        $('#workflowTitle').text(title);

        $('#workflowMessage').text(message);

        $('#workflowNoteArea').toggleClass('d-none', !requiresNote);

        $('#workflowNoteArea textarea').prop('required', requiresNote);

        $('#workflowSubmit')
            .removeClass('btn-primary btn-warning btn-danger btn-success')
            .addClass(buttonClass)
            .text(buttonText);

        new bootstrap.Modal(
            document.getElementById('workflowModal')
        ).show();

    });

});
</script>
@endsection