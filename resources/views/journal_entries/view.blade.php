@extends('layouts.core')
@section('title', 'View | Journal Entries')
    
@section('content')
    @include('journal_entries.partial.header')

    @php
        $status = [
            'draft' => [
                'label' => 'Draft',
                'class' => 'secondary',
                'icon' => 'fa-file',
            ],
            'posted' => [
                'label' => 'Posted',
                'class' => 'success',
                'icon' => 'fa-check-circle',
            ],
            'reversed' => [
                'label' => 'Reversed',
                'class' => 'danger',
                'icon' => 'fa-undo',
            ],
        ];

        $statusInfo = $status[$journalEntry->status] ?? [
            'label' => ucfirst($journalEntry->status),
            'class' => 'secondary',
            'icon' => 'fa-question',
        ];

        $totalDebit = $journalEntry->lines->sum('debit');
        $totalCredit = $journalEntry->lines->sum('credit');
    @endphp

    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-1">
                        {{ $journalEntry->entry_number }}
                    </h4>

                    <span class="badge bg-{{ $statusInfo['class'] }}">
                        <i class="fas {{ $statusInfo['icon'] }} me-1"></i>
                        {{ $statusInfo['label'] }}
                    </span>
                </div>

                <div class="text-muted">
                    Manual Journal Entry
                </div>
            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('journal_entries.index') }}"
                   class="btn btn-light">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back
                </a>

                @if($journalEntry->status === 'draft')
                    <a href="{{ route('journal_entries.edit', $journalEntry->id) }}"
                       class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>
                        Edit
                    </a>
                @endif

            </div>

        </div>


        <div class="row g-4">

            {{-- Journal Information --}}
            <div class="col-lg-8">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-book me-2"></i>
                            Journal Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Entry Number
                                </small>

                                <strong>
                                    {{ $journalEntry->entry_number }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Event Type
                                </small>

                                <span class="badge bg-info">
                                    {{ ucfirst(str_replace('_', ' ', $journalEntry->event_type)) }}
                                </span>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Transaction Date
                                </small>

                                <strong>
                                    {{ $journalEntry->transaction_date->format('d M Y') }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Posting Date
                                </small>

                                <strong>
                                    {{ $journalEntry->posting_date->format('d M Y') }}
                                </strong>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Accounting Period
                                </small>

                                <a href="{{ route('accounting_periods.show', $journalEntry->accountingPeriod->id) }}">
                                    {{ $journalEntry->accountingPeriod->name }}
                                </a>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Reference
                                </small>

                                <strong>
                                    {{ $journalEntry->reference_number ?: '—' }}
                                </strong>
                            </div>

                            <div class="col-12">
                                <small class="text-muted d-block">
                                    Description
                                </small>

                                <div class="mt-1">
                                    {!! nl2br(e($journalEntry->description)) !!}
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>


            {{-- Workflow --}}
            <div class="col-lg-4">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Workflow
                        </h5>
                    </div>

                    <div class="card-body">

                        @if($journalEntry->status === 'draft')

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                This journal has not been posted to the General Ledger.
                            </div>

                            <form method="POST"
                                  action="{{ route('journal_entries.post', $journalEntry->id) }}"
                                  class="mb-2"
                                  onsubmit="return confirm('Post this journal entry to the General Ledger?')">

                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="btn btn-success w-100">
                                    <i class="fas fa-check me-1"></i>
                                    Post Journal
                                </button>

                            </form>

                            <form method="POST"
                                  action="{{ route('journal_entries.destroy', $journalEntry->id) }}"
                                  onsubmit="return confirm('Delete this draft journal?')">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="btn btn-outline-danger w-100">
                                    <i class="fas fa-trash me-1"></i>
                                    Delete Draft
                                </button>

                            </form>

                        @elseif($journalEntry->status === 'posted')

                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                This journal has been posted to the General Ledger.
                            </div>

                            <button type="button"
                                    class="btn btn-danger w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reverseModal">
                                <i class="fas fa-undo me-1"></i>
                                Reverse Journal
                            </button>

                        @else

                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-undo me-2"></i>
                                This journal has been reversed.
                            </div>

                        @endif

                    </div>
                </div>

            </div>


            {{-- Journal Lines --}}
            <div class="col-12">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white d-flex justify-content-between align-items-center">

                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Journal Lines
                        </h5>

                        @if(abs($totalDebit - $totalCredit) < 0.01)
                            <span class="badge bg-success">
                                Balanced
                            </span>
                        @else
                            <span class="badge bg-danger">
                                Unbalanced
                            </span>
                        @endif

                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Account</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach($journalEntry->lines as $index => $line)

                                    <tr>

                                        <td>
                                            {{ $index + 1 }}
                                        </td>

                                        <td>
                                            <strong>
                                                {{ $line->account->code }}
                                            </strong>

                                            <div class="text-muted small">
                                                {{ $line->account->name }}
                                            </div>
                                        </td>

                                        <td class="text-end">
                                            @if($line->debit > 0)
                                                {{ number_format($line->debit, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            @if($line->credit > 0)
                                                {{ number_format($line->credit, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                            <tfoot class="table-light">

                                <tr>
                                    <th colspan="2" class="text-end">
                                        Total
                                    </th>

                                    <th class="text-end">
                                        {{ number_format($totalDebit, 2) }}
                                    </th>

                                    <th class="text-end">
                                        {{ number_format($totalCredit, 2) }}
                                    </th>
                                </tr>

                            </tfoot>

                        </table>

                    </div>
                </div>

            </div>


            {{-- Audit Information --}}
            <div class="col-md-6">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Audit Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Created By
                            </span>

                            <strong>
                                {{ optional($journalEntry->creator)->name ?? '—' }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Created At
                            </span>

                            <span>
                                {{ optional($journalEntry->created_at)->format('d M Y H:i') }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">
                                Posted By
                            </span>

                            <strong>
                                {{ optional($journalEntry->poster)->name ?? '—' }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">
                                Posted At
                            </span>

                            <span>
                                {{ optional($journalEntry->posted_at)->format('d M Y H:i') ?? '—' }}
                            </span>
                        </div>

                    </div>
                </div>

            </div>


            {{-- Reversal Information --}}
            @if($journalEntry->reversal_of_id || $journalEntry->reversals->isNotEmpty())

                <div class="col-md-6">

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-undo me-2"></i>
                                Reversal Information
                            </h5>
                        </div>

                        <div class="card-body">

                            @if($journalEntry->reversalOf)

                                <div class="mb-3">
                                    <small class="text-muted d-block">
                                        Reversal Of
                                    </small>

                                    <a href="{{ route('journal_entries.show', $journalEntry->reversalOf->id) }}">
                                        {{ $journalEntry->reversalOf->entry_number }}
                                    </a>
                                </div>

                                <div>
                                    <small class="text-muted d-block">
                                        Reason
                                    </small>

                                    {{ $journalEntry->reversal_reason }}
                                </div>

                            @endif

                            @foreach($journalEntry->reversals as $reversal)

                                <div class="mb-3">
                                    <small class="text-muted d-block">
                                        Reversal Entry
                                    </small>

                                    <a href="{{ route('journal_entries.show', $reversal->id) }}">
                                        {{ $reversal->entry_number }}
                                    </a>
                                </div>

                            @endforeach

                        </div>
                    </div>

                </div>

            @endif

        </div>

    </div>


    {{-- Reversal Modal --}}
    @if($journalEntry->status === 'posted')

        <div class="modal fade"
             id="reverseModal"
             tabindex="-1">

            <div class="modal-dialog">

                <form method="POST"
                      action="{{ route('journal_entries.reverse', $journalEntry->id) }}"
                      class="modal-content">

                    @csrf
                    @method('POST')

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Reverse Journal Entry
                        </h5>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="alert alert-warning">
                            This will create a reversal journal and mark
                            <strong>{{ $journalEntry->entry_number }}</strong>
                            as reversed.
                        </div>

                        <label class="form-label">
                            Reversal Reason <span class="text-danger">*</span>
                        </label>

                        <textarea name="reversal_reason"
                                  class="form-control"
                                  rows="4"
                                  required></textarea>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-danger">
                            <i class="fas fa-undo me-1"></i>
                            Reverse Journal
                        </button>

                    </div>

                </form>

            </div>

        </div>

    @endif
@endsection
