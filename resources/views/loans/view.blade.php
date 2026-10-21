@extends('layouts.core')
@section('title', 'Loan Accounts')

@section('content')
    @include('loans.partial.header')
    @php
        $statusClasses = [
            'active' => 'success',
            'in_arrears' => 'danger',
            'restructured' => 'warning',
            'fully_paid' => 'info',
            'written_off' => 'dark',
            'closed' => 'secondary',
        ];

        $statusClass = $statusClasses[$loan->status] ?? 'secondary';

        $money = function ($value) {
            return number_format((float) $value, 2);
        };

        $date = function ($value) {
            return $value
                ? \Carbon\Carbon::parse($value)->format('d M Y')
                : '—';
        };

        $progress = $loan->approved_amount > 0
            ? min(100, ($loan->amount_disbursed / $loan->approved_amount) * 100)
            : 0;

        $repaymentProgress = ($loan->approved_amount + $loan->interest_balance + $loan->fees_due) > 0
            ? min(
                100,
                ($loan->total_paid /
                    ($loan->approved_amount + $loan->interest_balance + $loan->fees_due)
                ) * 100
            )
            : 0;
    @endphp


    <div class="container-fluid">

        {{-- =====================================================
             STATUS BANNER
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="rounded-circle bg-light
                                            d-flex align-items-center
                                            justify-content-center"
                                     style="width:55px;height:55px;">
                                    <i class="fa fa-money-bill-wave fa-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    {{ $loan->loan_number }}
                                </h5>
                                <div class="text-muted">
                                    @if(isset($loan->member))
                                        {{ @$loan->member->full_name }}                                    
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-{{ $statusClass }} fs-6 px-3 py-2">
                            {{ ucwords(str_replace('_', ' ', $loan->status)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             FINANCIAL SUMMARY
        ====================================================== --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Approved Amount
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->approved_amount) }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Amount Disbursed
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->amount_disbursed) }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Total Paid
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->total_paid) }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Outstanding Balance
                        </div>
                        <h5 class="mb-0 text-danger">
                            {{ $money($loan->total_outstanding_balance) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             BALANCE POSITION
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Loan Balance Position
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Principal --}}
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Principal Balance
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->principal_balance) }}
                        </h5>
                        <small class="text-muted">
                            Paid: {{ $money($loan->principal_paid) }}
                        </small>
                    </div>
                    {{-- Interest --}}
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Interest Balance
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->interest_balance) }}
                        </h5>
                        <small class="text-muted">
                            Paid: {{ $money($loan->interest_paid) }}
                        </small>
                    </div>
                    {{-- Fees --}}
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Fees Balance
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->fees_balance) }}
                        </h5>
                        <small class="text-muted">
                            Paid: {{ $money($loan->fees_paid) }}
                        </small>
                    </div>
                    {{-- Penalty --}}
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Penalty Balance
                        </div>
                        <h5 class="mb-0">
                            {{ $money($loan->penalty_balance) }}
                        </h5>
                        <small class="text-muted">
                            Paid: {{ $money($loan->penalties_paid) }}
                        </small>
                    </div>
                    {{-- Total Paid --}}
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Total Paid
                        </div>
                        <h5 class="mb-0 text-success">
                            {{ $money($loan->total_paid) }}
                        </h5>
                    </div>
                    {{-- Outstanding --}}
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Total Outstanding
                        </div>
                        <h5 class="mb-0 text-danger">
                            {{ $money($loan->total_outstanding_balance) }}
                        </h5>
                    </div>
                </div>
                {{-- Disbursement Progress --}}
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">
                            Disbursement
                        </span>
                        <span class="small fw-semibold">
                            {{ number_format($progress, 1) }}%
                        </span>
                    </div>
                    <div class="progress"
                         style="height:8px;">
                        <div class="progress-bar"
                             style="width:{{ $progress }}%;">
                        </div>
                    </div>
                </div>
                {{-- Repayment Progress --}}
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">
                            Repayment Progress
                        </span>
                        <span class="small fw-semibold">
                            {{ number_format($repaymentProgress, 1) }}%
                        </span>
                    </div>
                    <div class="progress"
                         style="height:8px;">
                        <div class="progress-bar bg-success"
                             style="width:{{ $repaymentProgress }}%;">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             LOAN TERMS
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Loan Terms
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="text-muted small">
                            Repayment Period
                        </div>
                        <div class="fw-semibold">
                            {{ $loan->repayment_period_months }} Months
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-muted small">
                            Interest Rate
                        </div>
                        <div class="fw-semibold">
                            {{ number_format($loan->interest_rate, 4) }}%
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-muted small">
                            Interest Method
                        </div>
                        <div>
                            {{ ucwords(str_replace('_', ' ', $loan->interest_method)) }}
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-muted small">
                            Monthly Installment
                        </div>
                        <div class="fw-semibold">
                            {{ $money($loan->monthly_installment) }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">
                            Payment Mode
                        </div>
                        <div>
                            {{ ucwords(str_replace('_', ' ', $loan->payment_mode)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             REPAYMENT DATES
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Repayment Dates
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Disbursement Date
                        </div>
                        <div class="fw-semibold">
                            {{ $date($loan->disbursement_date) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            First Repayment Date
                        </div>
                        <div class="fw-semibold">
                            {{ $date($loan->first_repayment_date) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Maturity Date
                        </div>
                        <div class="fw-semibold">
                            {{ $date($loan->maturity_date) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             DISBURSEMENT
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Disbursement Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Disbursement Method
                        </div>
                        <div>
                            {{ $loan->disbursement_method
                                ? ucwords(str_replace('_', ' ', $loan->disbursement_method))
                                : '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Disbursement Reference
                        </div>
                        <div>
                            {{ $loan->disbursement_reference ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Disbursed By
                        </div>
                        <div>
                            {{ $loan->disbursed_by ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Approved Amount
                        </div>
                        <div>
                            {{ $money($loan->approved_amount) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Amount Disbursed
                        </div>
                        <div>
                            {{ $money($loan->amount_disbursed) }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Undisbursed Amount
                        </div>
                        <div>
                            {{ $money(max(0, $loan->approved_amount - $loan->amount_disbursed)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             ARREARS
        ====================================================== --}}
        @if($loan->arrears_amount > 0 || $loan->days_in_arrears > 0)
            <div class="card shadow-sm border-danger mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 text-danger">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        Arrears
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Arrears Amount
                            </div>
                            <h5 class="text-danger mb-0">
                                {{ $money($loan->arrears_amount) }}
                            </h5>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Days in Arrears
                            </div>
                            <h5 class="text-danger mb-0">
                                {{ number_format($loan->days_in_arrears) }}
                            </h5>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Loan Status
                            </div>
                            <span class="badge bg-danger">
                                {{ ucwords(str_replace('_', ' ', $loan->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif


        {{-- =====================================================
             REPAYMENT SCHEDULE
        ====================================================== --}}
        @if(isset($loan->repaymentSchedules))
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">
                        Repayment Schedule
                    </h6>
                    <span class="text-muted small">
                        {{ $loan->repaymentSchedules->count() }} Installments
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($loan->repaymentSchedules->count())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Due Date</th>
                                        <th class="text-end">Principal</th>
                                        <th class="text-end">Interest</th>
                                        <th class="text-end">Fees</th>
                                        <th class="text-end">Total Due</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loan->repaymentSchedules as $schedule)
                                        @php
                                            $scheduleStatus = [
                                                'pending' => 'secondary',
                                                'partially_paid' => 'warning',
                                                'paid' => 'success',
                                                'overdue' => 'danger',
                                                'waived' => 'dark',
                                            ];
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $schedule->installment_number }}
                                            </td>
                                            <td>
                                                {{ $date($schedule->due_date) }}
                                            </td>
                                            <td class="text-end">
                                                {{ $money($schedule->principal_due) }}
                                            </td>
                                            <td class="text-end">
                                                {{ $money($schedule->interest_due) }}
                                            </td>
                                            <td class="text-end">
                                                {{ $money($schedule->fees_due) }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                {{ $money($schedule->total_due) }}
                                            </td>
                                            <td class="text-end text-success">
                                                {{ $money($schedule->total_paid) }}
                                            </td>
                                            <td class="text-end text-danger">
                                                {{ $money($schedule->outstanding_amount) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $scheduleStatus[$schedule->status] ?? 'secondary' }}">
                                                    {{ ucwords(str_replace('_', ' ', $schedule->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            No repayment schedule has been created.
                        </div>
                    @endif
                </div>
            </div>
        @endif


        {{-- =====================================================
             CLOSURE
        ====================================================== --}}
        @if($loan->status === 'closed' || $loan->closed_date)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        Loan Closure
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Closed Date
                            </div>
                            <div>
                                {{ $date($loan->closed_date) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Closed By
                            </div>
                            <div>
                                {{ $loan->closed_by ?: '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Closure Reason
                            </div>
                            <div>
                                {{ $loan->closure_reason ?: '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif


        {{-- =====================================================
             REFERENCES
        ====================================================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Loan References
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Loan Account ID
                        </div>
                        <div>
                            #{{ $loan->loan_number }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Member ID
                        </div>
                        <div>
                            #{{ @$loan->member->membership_number }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Application ID
                        </div>
                        <div>
                            #{{ @$loan->loanApplication->application_number }}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- =====================================================
             AUDIT
        ====================================================== --}}
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Audit Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Created
                        </div>
                        <div>
                            {{ optional($loan->created_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Last Updated
                        </div>
                        <div>
                            {{ optional($loan->updated_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection