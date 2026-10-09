@extends('layouts.core')
@section('title', 'Create | Loan Repayments')

@section('content')
    @include('loan_repayments.partial.header')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">            
            <div class="d-flex gap-2">
                
                @if($loanRepayment->status === 'pending')
                    <a href="{{ route('loan_repayments.edit', $loanRepayment->id) }}"
                       class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <form method="POST"
                          action="{{ route('loan_repayments.confirm', $loanRepayment->id) }}"
                          class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-success"
                                onclick="return confirm('Confirm and allocate this repayment?')">
                            <i class="fa fa-check"></i> Confirm
                        </button>
                    </form>
                @endif
                @if($loanRepayment->status === 'confirmed')
                    <button type="button"
                            class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#reverseModal">
                        <i class="fa fa-undo"></i> Reverse
                    </button>
                @endif
            </div>
        </div>
        {{-- Status --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">
                            {{ $loanRepayment->repayment_number }}
                        </h5>
                        <div class="text-muted">
                            Payment received on
                            {{ \Carbon\Carbon::parse($loanRepayment->payment_date)->format('d M Y') }}
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        @php
                            $statusClass = [
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'failed' => 'danger',
                                'reversed' => 'dark',
                            ][$loanRepayment->status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $statusClass }} fs-6 px-3 py-2">
                            {{ ucfirst($loanRepayment->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        {{-- Payment Summary --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Amount Paid
                        </div>
                        <h4 class="mb-0">
                            KES {{ number_format($loanRepayment->amount_paid, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Principal
                        </div>
                        <h4 class="mb-0 text-primary">
                            KES {{ number_format($loanRepayment->principal_amount, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Interest
                        </div>
                        <h5 class="mb-0">
                            KES {{ number_format($loanRepayment->interest_amount, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Fees
                        </div>
                        <h5 class="mb-0">
                            KES {{ number_format($loanRepayment->fees_amount, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Penalty
                        </div>
                        <h5 class="mb-0">
                            KES {{ number_format($loanRepayment->penalty_amount, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        {{-- Member / Loan Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Member & Loan Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Member
                        </label>
                        <div class="fw-semibold">
                            {{ optional($loanRepayment->member)->name ?? '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Loan Number
                        </label>
                        <div class="fw-semibold">
                            {{ optional($loanRepayment->loan)->loan_number ?? '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Payment Method
                        </label>
                        <div>
                            {{ ucwords(str_replace('_', ' ', $loanRepayment->payment_method)) }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Payment Date
                        </label>
                        <div>
                            {{ \Carbon\Carbon::parse($loanRepayment->payment_date)->format('d M Y') }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Value Date
                        </label>
                        <div>
                            {{ $loanRepayment->value_date
                                ? \Carbon\Carbon::parse($loanRepayment->value_date)->format('d M Y')
                                : '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Transaction Reference
                        </label>
                        <div>
                            {{ $loanRepayment->transaction_reference ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Receipt Number
                        </label>
                        <div>
                            {{ $loanRepayment->receipt_number ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Payer
                        </label>
                        <div>
                            {{ $loanRepayment->payer_name ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small">
                            Payer Phone
                        </label>
                        <div>
                            {{ $loanRepayment->payer_phone ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Allocation Summary --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between">
                <h6 class="mb-0">
                    Repayment Allocation
                </h6>
                <span class="text-muted">
                    {{ $loanRepayment->allocations->count() }} schedule(s)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Installment</th>
                            <th>Due Date</th>
                            <th class="text-end">Principal</th>
                            <th class="text-end">Interest</th>
                            <th class="text-end">Fees</th>
                            <th class="text-end">Penalty</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loanRepayment->allocations as $allocation)
                            <tr>
                                <td>
                                    #{{ optional($allocation->schedule)->installment_number ?? '—' }}
                                </td>
                                <td>
                                    {{ optional($allocation->schedule)->due_date
                                        ? \Carbon\Carbon::parse($allocation->schedule->due_date)->format('d M Y')
                                        : '—' }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($allocation->principal_allocated, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($allocation->interest_allocated, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($allocation->fees_allocated, 2) }}
                                </td>
                                <td class="text-end">
                                    {{ number_format($allocation->penalty_allocated, 2) }}
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ number_format($allocation->total_allocated, 2) }}
                                </td>
                                <td>
                                    @if($allocation->status === 'active')
                                        <span class="badge bg-success">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            Reversed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    class="text-center text-muted py-4">
                                    No repayment allocations yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($loanRepayment->allocations->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2">
                                    Total
                                </th>
                                <th class="text-end">
                                    {{ number_format($loanRepayment->allocations->sum('principal_allocated'), 2) }}
                                </th>
                                <th class="text-end">
                                    {{ number_format($loanRepayment->allocations->sum('interest_allocated'), 2) }}
                                </th>
                                <th class="text-end">
                                    {{ number_format($loanRepayment->allocations->sum('fees_allocated'), 2) }}
                                </th>
                                <th class="text-end">
                                    {{ number_format($loanRepayment->allocations->sum('penalty_allocated'), 2) }}
                                </th>
                                <th class="text-end">
                                    {{ number_format($loanRepayment->allocations->sum('total_allocated'), 2) }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        {{-- Unallocated Amount --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            Unallocated Amount
                        </h6>
                        <div class="text-muted small">
                            Amount received but not allocated to the
                            loan repayment schedules.
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <h5 class="mb-0
                            {{ $loanRepayment->unallocated_amount > 0
                                ? 'text-warning'
                                : 'text-success' }}">
                            KES
                            {{ number_format($loanRepayment->unallocated_amount, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        {{-- Audit Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Audit Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Recorded By
                        </div>
                        <div>
                            {{ optional($loanRepayment->recordedBy)->name ?? '—' }}                            
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Created
                        </div>
                        <div>
                            {{ dateFormat($loanRepayment->created_at, 'd M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Last Updated
                        </div>
                        <div>
                            {{ dateFormat($loanRepayment->updated_at, 'd M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Reversal Information --}}
        @if($loanRepayment->status === 'reversed')
            <div class="card border-danger shadow-sm mb-4">
                <div class="card-header text-danger bg-white">
                    <h6 class="mb-0">
                        Reversal Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Reversed By
                            </div>
                            <div>
                                {{ optional($loanRepayment->reversedBy)->name ?? '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">
                                Reversed At
                            </div>
                            <div>
                                {{ $loanRepayment->reversed_at
                                    ? \Carbon\Carbon::parse($loanRepayment->reversed_at)->format('d M Y H:i')
                                    : '—' }}
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <div class="text-muted small">
                                Reversal Reason
                            </div>
                            <div>
                                {{ $loanRepayment->reversal_reason ?: '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Remarks --}}
        @if($loanRepayment->remarks)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Remarks</h6>
                </div>
                <div class="card-body">
                    {{ $loanRepayment->remarks }}
                </div>
            </div>
        @endif
    </div>

    {{-- Reverse Modal --}}
    @if($loanRepayment->status === 'confirmed')
        <div class="modal fade"
             id="reverseModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('loan_repayments.reverse', $loanRepayment->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Reverse Loan Repayment
                            </h5>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                Reversing this repayment will restore the
                                affected loan balances and repayment schedules.
                                The transaction will not be deleted.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    Reversal Reason
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea name="reversal_reason"
                                          class="form-control"
                                          rows="4"
                                          required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-light"
                                    data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="btn btn-danger">
                                Confirm Reversal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
