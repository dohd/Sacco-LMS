@extends('layouts.core')
@section('title', 'Loan Repayment Schedules')

@section('content')
    @include('loan_repayment_schedules.partial.header')
    @php
        $statusClasses = [
            'pending' => 'bg-warning text-dark',
            'partially_paid' => 'bg-info text-dark',
            'paid' => 'bg-success',
            'overdue' => 'bg-danger',
            'waived' => 'bg-secondary',
        ];

        $status = $schedule->status;
    @endphp
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    Installment #{{ $schedule->installment_number }}
                </h4>

                <div class="text-muted">
                    {{ $schedule->loan->loan_number ?? 'Loan #' . $schedule->loan_id }}
                </div>
            </div>
        </div>


        {{-- SUMMARY CARDS --}}
        <div class="row g-3 mb-4">

            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="text-muted small mb-2">
                            Status
                        </div>

                        <span class="badge {{ $statusClasses[$status] ?? 'bg-secondary' }} px-3 py-2">
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="text-muted small">
                            Total Due
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ number_format($schedule->total_due, 2) }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="text-muted small">
                            Total Paid
                        </div>

                        <div class="fs-4 fw-bold text-success">
                            {{ number_format($schedule->total_paid, 2) }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body">

                        <div class="text-muted small">
                            Outstanding
                        </div>

                        <div class="fs-4 fw-bold text-danger">
                            {{ number_format($schedule->outstanding_amount, 2) }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="row g-4">

            {{-- MAIN --}}
            <div class="col-xl-8">

                {{-- INSTALLMENT INFORMATION --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-calendar-check text-primary me-2"></i>
                            Installment Information
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-4">

                                <label class="text-muted small d-block">
                                    Installment Number
                                </label>

                                <div class="fw-semibold">
                                    #{{ $schedule->installment_number }}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <label class="text-muted small d-block">
                                    Due Date
                                </label>

                                <div class="fw-semibold">
                                    {{ \Carbon\Carbon::parse($schedule->due_date)->format('d M Y') }}
                                </div>

                            </div>


                            <div class="col-md-4">

                                <label class="text-muted small d-block">
                                    Status
                                </label>

                                <span class="badge {{ $statusClasses[$status] ?? 'bg-secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </span>

                            </div>


                            <div class="col-md-6">

                                <label class="text-muted small d-block">
                                    Loan Account
                                </label>

                                <div class="fw-semibold">

                                    @if($schedule->loan)

                                        <a href="{{ route('loans.show', $schedule->loan->id) }}">
                                            {{ $schedule->loan->loan_number }}
                                        </a>

                                    @else
                                        —
                                    @endif

                                </div>

                            </div>


                            <div class="col-md-6">

                                <label class="text-muted small d-block">
                                    Member
                                </label>

                                <div class="fw-semibold">
                                    {{ $schedule->member->name ?? '—' }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- PRINCIPAL MOVEMENT --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-chart-line text-primary me-2"></i>
                            Principal Movement
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-4">

                                <div class="border rounded p-3 h-100">

                                    <div class="text-muted small">
                                        Opening Principal
                                    </div>

                                    <div class="fs-5 fw-bold mt-1">
                                        {{ number_format($schedule->opening_principal_balance, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="border rounded p-3 h-100">

                                    <div class="text-muted small">
                                        Principal Due
                                    </div>

                                    <div class="fs-5 fw-bold text-primary mt-1">
                                        {{ number_format($schedule->principal_due, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="border rounded p-3 h-100">

                                    <div class="text-muted small">
                                        Closing Principal
                                    </div>

                                    <div class="fs-5 fw-bold text-success mt-1">
                                        {{ number_format($schedule->closing_principal_balance, 2) }}
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="progress mt-4"
                             style="height: 10px;">

                            @php
                                $opening = (float) $schedule->opening_principal_balance;
                                $principalPaid = (float) $schedule->principal_paid;

                                $principalProgress = $opening > 0
                                    ? min(100, ($principalPaid / $opening) * 100)
                                    : 0;
                            @endphp

                            <div class="progress-bar bg-success"
                                 role="progressbar"
                                 style="width: {{ $principalProgress }}%;">
                            </div>

                        </div>

                        <div class="d-flex justify-content-between mt-2">

                            <small class="text-muted">
                                Principal paid
                            </small>

                            <small class="fw-semibold">
                                {{ number_format($principalProgress, 1) }}%
                            </small>

                        </div>

                    </div>

                </div>


                {{-- SCHEDULED AMOUNTS --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-calculator text-primary me-2"></i>
                            Scheduled Amounts
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table align-middle mb-0">

                                <thead class="table-light">

                                    <tr>
                                        <th>Component</th>
                                        <th class="text-end">Scheduled</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    @php
                                        $principalOutstanding =
                                            max(0, $schedule->principal_due - $schedule->principal_paid);

                                        $interestOutstanding =
                                            max(0, $schedule->interest_due - $schedule->interest_paid);

                                        $feesOutstanding =
                                            max(0, $schedule->fees_due - $schedule->fees_paid);

                                        $penaltyOutstanding =
                                            max(0, $schedule->penalty_due - $schedule->penalty_paid);
                                    @endphp

                                    <tr>

                                        <td class="fw-semibold">
                                            Principal
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($schedule->principal_due, 2) }}
                                        </td>

                                        <td class="text-end text-success">
                                            {{ number_format($schedule->principal_paid, 2) }}
                                        </td>

                                        <td class="text-end text-danger">
                                            {{ number_format($principalOutstanding, 2) }}
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="fw-semibold">
                                            Interest
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($schedule->interest_due, 2) }}
                                        </td>

                                        <td class="text-end text-success">
                                            {{ number_format($schedule->interest_paid, 2) }}
                                        </td>

                                        <td class="text-end text-danger">
                                            {{ number_format($interestOutstanding, 2) }}
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="fw-semibold">
                                            Fees
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($schedule->fees_due, 2) }}
                                        </td>

                                        <td class="text-end text-success">
                                            {{ number_format($schedule->fees_paid, 2) }}
                                        </td>

                                        <td class="text-end text-danger">
                                            {{ number_format($feesOutstanding, 2) }}
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="fw-semibold">
                                            Penalty
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($schedule->penalty_due, 2) }}
                                        </td>

                                        <td class="text-end text-success">
                                            {{ number_format($schedule->penalty_paid, 2) }}
                                        </td>

                                        <td class="text-end text-danger">
                                            {{ number_format($penaltyOutstanding, 2) }}
                                        </td>

                                    </tr>

                                </tbody>


                                <tfoot>

                                    <tr class="table-light">

                                        <th>
                                            Total
                                        </th>

                                        <th class="text-end">
                                            {{ number_format($schedule->total_due, 2) }}
                                        </th>

                                        <th class="text-end text-success">
                                            {{ number_format($schedule->total_paid, 2) }}
                                        </th>

                                        <th class="text-end text-danger">
                                            {{ number_format($schedule->outstanding_amount, 2) }}
                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- PAYMENT STATUS --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-money-bill-wave text-primary me-2"></i>
                            Payment Allocation
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-3">

                                <div class="border rounded p-3">

                                    <div class="text-muted small">
                                        Principal Paid
                                    </div>

                                    <div class="fw-bold text-success mt-1">
                                        {{ number_format($schedule->principal_paid, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="border rounded p-3">

                                    <div class="text-muted small">
                                        Interest Paid
                                    </div>

                                    <div class="fw-bold text-success mt-1">
                                        {{ number_format($schedule->interest_paid, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="border rounded p-3">

                                    <div class="text-muted small">
                                        Fees Paid
                                    </div>

                                    <div class="fw-bold text-success mt-1">
                                        {{ number_format($schedule->fees_paid, 2) }}
                                    </div>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="border rounded p-3">

                                    <div class="text-muted small">
                                        Penalty Paid
                                    </div>

                                    <div class="fw-bold text-success mt-1">
                                        {{ number_format($schedule->penalty_paid, 2) }}
                                    </div>

                                </div>

                            </div>

                        </div>


                        @if($schedule->fully_paid_date)

                            <div class="alert alert-success border-0 mt-4 mb-0">

                                <i class="fa fa-check-circle me-2"></i>

                                This installment was fully paid on
                                <strong>
                                    {{ \Carbon\Carbon::parse($schedule->fully_paid_date)->format('d M Y') }}
                                </strong>.

                            </div>

                        @endif

                    </div>

                </div>


                {{-- REMARKS --}}
                @if($schedule->remarks)

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header bg-white py-3">

                            <h6 class="mb-0 fw-bold">
                                <i class="fa fa-comment-alt text-primary me-2"></i>
                                Remarks
                            </h6>

                        </div>

                        <div class="card-body">

                            {!! nl2br(e($schedule->remarks)) !!}

                        </div>

                    </div>

                @endif

            </div>


            {{-- SIDEBAR --}}
            <div class="col-xl-4">

                {{-- OUTSTANDING SUMMARY --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-wallet text-primary me-2"></i>
                            Payment Summary
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">
                                Total Due
                            </span>

                            <strong>
                                {{ number_format($schedule->total_due, 2) }}
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-muted">
                                Total Paid
                            </span>

                            <strong class="text-success">
                                {{ number_format($schedule->total_paid, 2) }}
                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between">

                            <span class="fw-semibold">
                                Outstanding
                            </span>

                            <strong class="text-danger fs-5">
                                {{ number_format($schedule->outstanding_amount, 2) }}
                            </strong>

                        </div>

                    </div>

                </div>


                {{-- DUE DATE --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body text-center">

                        <div class="text-muted small">
                            Payment Due Date
                        </div>

                        <div class="display-6 fw-bold mt-2">
                            {{ \Carbon\Carbon::parse($schedule->due_date)->format('d') }}
                        </div>

                        <div class="fw-semibold">
                            {{ \Carbon\Carbon::parse($schedule->due_date)->format('M Y') }}
                        </div>

                        @if($status === 'overdue')

                            <div class="badge bg-danger mt-3">
                                Overdue
                            </div>

                        @elseif($status === 'paid')

                            <div class="badge bg-success mt-3">
                                Paid
                            </div>

                        @elseif($status === 'pending')

                            <div class="badge bg-warning text-dark mt-3">
                                Pending
                            </div>

                        @endif

                    </div>

                </div>


                {{-- RECORD INFORMATION --}}
                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-white py-3">

                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-history text-primary me-2"></i>
                            Record Information
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="mb-3">

                            <div class="text-muted small">
                                Created
                            </div>

                            <div class="fw-semibold">
                                {{ optional($schedule->created_at)->format('d M Y H:i') }}
                            </div>

                        </div>


                        <div>

                            <div class="text-muted small">
                                Last Updated
                            </div>

                            <div class="fw-semibold">
                                {{ optional($schedule->updated_at)->format('d M Y H:i') }}
                            </div>

                        </div>

                    </div>

                </div>


                {{-- ACTIONS --}}
                @if($schedule->total_paid <= 0 && $status !== 'waived')

                    <div class="card border-0 shadow-sm">

                        <div class="card-body">

                            <a href="{{ route('loan_repayment_schedules.edit', $schedule->id) }}"
                               class="btn btn-primary w-100">

                                <i class="fa fa-edit me-1"></i>
                                Edit Schedule

                            </a>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>
@endsection