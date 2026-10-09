@extends('layouts.core')
@section('title', 'View | Accounting Periods Management')
    
@section('content')
    @include('accounting_periods.partial.header')
    @php
        $statusClasses = [
            'open' => 'success',
            'closed' => 'warning',
            'locked' => 'dark',
        ];

        $statusIcons = [
            'open' => 'fa-unlock',
            'closed' => 'fa-lock',
            'locked' => 'fa-lock',
        ];

        $statusClass = $statusClasses[$accountingPeriod->status] ?? 'secondary';
        $statusIcon = $statusIcons[$accountingPeriod->status] ?? 'fa-circle';
    @endphp

    <div class="container-fluid">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">
                    {{ $accountingPeriod->name }}
                </h4>
                <div class="text-muted">
                    Financial Year {{ $accountingPeriod->financial_year }}
                </div>
            </div>
            <div class="d-flex gap-2">                
                @if($accountingPeriod->status === 'open')
                    <a href="{{ route('accounting_periods.edit', $accountingPeriod->id) }}"
                       class="btn btn-primary">
                        <i class="fa fa-edit"></i>
                        Edit
                    </a>
                    <button type="button"
                            class="btn btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#closePeriodModal">
                        <i class="fa fa-lock"></i>
                        Close Period
                    </button>
                @elseif($accountingPeriod->status === 'closed')
                    <button type="button"
                            class="btn btn-dark"
                            data-bs-toggle="modal"
                            data-bs-target="#lockPeriodModal">
                        <i class="fa fa-lock"></i>
                        Lock Period
                    </button>
                @endif
            </div>
        </div>
        {{-- Status Banner --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="rounded-circle bg-{{ $statusClass }}
                                             text-white d-flex align-items-center
                                             justify-content-center"
                                      style="width:48px;height:48px;">
                                    <i class="fa {{ $statusIcon }}"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    Accounting Period
                                </h5>
                                <div class="text-muted">
                                    {{ $accountingPeriod->start_date }} to {{ $accountingPeriod->end_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-{{ $statusClass }} fs-6 px-3 py-2">
                            {{ ucfirst($accountingPeriod->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Financial Year
                        </div>
                        <h4 class="mb-0">
                            {{ $accountingPeriod->financial_year }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Start Date
                        </div>
                        <h5 class="mb-0">
                            {{ \Carbon\Carbon::parse($accountingPeriod->start_date)->format('d M Y') }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            End Date
                        </div>
                        <h5 class="mb-0">
                            {{ \Carbon\Carbon::parse($accountingPeriod->end_date)->format('d M Y') }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Duration
                        </div>
                        <h5 class="mb-0">
                            {{ \Carbon\Carbon::parse($accountingPeriod->start_date)
                                ->diffInDays(
                                    \Carbon\Carbon::parse($accountingPeriod->end_date)
                                ) + 1 }}
                            Days
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        {{-- Period Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Period Details
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Period Name
                        </div>
                        <div class="fw-semibold">
                            {{ $accountingPeriod->name }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Financial Year
                        </div>
                        <div class="fw-semibold">
                            {{ $accountingPeriod->financial_year }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Start Date
                        </div>
                        <div>
                            {{ \Carbon\Carbon::parse($accountingPeriod->start_date)->format('d F Y') }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            End Date
                        </div>
                        <div>
                            {{ \Carbon\Carbon::parse($accountingPeriod->end_date)->format('d F Y') }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">
                            Current Status
                        </div>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst($accountingPeriod->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Closure Information --}}
        @if($accountingPeriod->status !== 'open')
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        Closure & Control Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Closed By
                            </div>
                            <div class="fw-semibold">
                                {{ optional($accountingPeriod->closedBy)->name ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Closed At
                            </div>
                            <div>
                                {{ $accountingPeriod->closed_at
                                    ? \Carbon\Carbon::parse($accountingPeriod->closed_at)
                                        ->format('d M Y H:i')
                                    : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Control Status
                            </div>
                            @if($accountingPeriod->status === 'locked')
                                <span class="badge bg-dark">
                                    Locked
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    Closed
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Accounting Control --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Accounting Control
                </h6>
            </div>
            <div class="card-body">
                @if($accountingPeriod->status === 'open')
                    <div class="alert alert-success mb-0">
                        <i class="fa fa-check-circle me-2"></i>
                        <strong>Period is open.</strong>
                        Transactions may be posted within this
                        accounting period.
                    </div>
                @elseif($accountingPeriod->status === 'closed')
                    <div class="alert alert-warning mb-0">
                        <i class="fa fa-lock me-2"></i>
                        <strong>Period is closed.</strong>
                        Normal posting to this period should no longer
                        be permitted.
                    </div>
                @else
                    <div class="alert alert-dark mb-0">
                        <i class="fa fa-lock me-2"></i>
                        <strong>Period is locked.</strong>
                        This period should be completely protected
                        from financial modifications.
                    </div>
                @endif
            </div>
        </div>
        {{-- Audit --}}
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
                            {{ optional($accountingPeriod->created_at)
                                ->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Last Updated
                        </div>
                        <div>
                            {{ optional($accountingPeriod->updated_at)
                                ->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Period ID
                        </div>
                        <div>
                            #{{ $accountingPeriod->id }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ============================================================
         CLOSE PERIOD MODAL
    ============================================================= --}}
    @if($accountingPeriod->status === 'open')
        <div class="modal fade"
             id="closePeriodModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('accounting_periods.close', $accountingPeriod->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Close Accounting Period
                            </h5>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                You are about to close:
                                <strong>
                                    {{ $accountingPeriod->name }}
                                </strong>
                                <br>
                                Transactions should no longer be posted
                                to this period after closure.
                            </div>
                            <p class="mb-0">
                                Are you sure you want to continue?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-light"
                                    data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="btn btn-warning">
                                <i class="fa fa-lock"></i>
                                Close Period
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ============================================================
         LOCK PERIOD MODAL
    ============================================================= --}}
    @if($accountingPeriod->status === 'closed')
        <div class="modal fade"
             id="lockPeriodModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('accounting_periods.lock', $accountingPeriod->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Lock Accounting Period
                            </h5>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="fa fa-lock me-2"></i>
                                <strong>Warning:</strong>
                                Locking a period should be treated as
                                a final accounting control.
                            </div>
                            <p>
                                Once locked, the period should not allow
                                financial transactions to be posted,
                                edited or deleted.
                            </p>
                            <p class="mb-0">
                                Are you sure you want to lock
                                <strong>{{ $accountingPeriod->name }}</strong>?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-light"
                                    data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="btn btn-dark">
                                <i class="fa fa-lock"></i>
                                Lock Period
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
