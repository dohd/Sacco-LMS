@extends('layouts.core')
@section('title', 'Nomination Management | View')

@section('content')
<div class="container-fluid">
    @include('nominations.partial.header')

    @php
        $statusClasses = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        $statusClass = $statusClasses[$nomination->status] ?? 'secondary';
        $totalPercentage = (float) $nomination->nominees->sum('percentage');
        $allocationValid = abs($totalPercentage - 100) < 0.01;
    @endphp

    <!-- Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center p-3">
            <div>
                <h4 class="mb-1">Nomination Details</h4>
                <div class="text-muted">
                    Member:
                    <strong>{{ $nomination->member->membership_number ?? $nomination->member_id }}</strong>
                    -
                    {{ $nomination->member->full_name ?? '' }}
                </div>
            </div>

            <div class="text-end">
                <span class="badge bg-{{ $statusClass }} fs-6">
                    {{ ucfirst($nomination->status) }}
                </span>

                @if($nomination->is_active)
                    <span class="badge bg-success fs-6 ms-1">Active</span>
                @else
                    <span class="badge bg-secondary fs-6 ms-1">Inactive</span>
                @endif

                <div class="small text-muted mt-2">
                    Created {{ optional($nomination->created_at)->format('d M Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- Main Content -->
        <div class="col-lg-9">

            <!-- Member Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Member Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Member Number</small>
                            <strong>{{ $nomination->member->membership_number ?? '-' }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Member Name</small>
                            <strong>{{ $nomination->member->full_name ?? '-' }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">National ID</small>
                            <strong>{{ $nomination->member->national_id ?? '-' }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Phone</small>
                            <strong>{{ $nomination->member->phone ?? '-' }}</strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Declaration Date</small>
                            <strong>
                                {{ $nomination->declaration_date
                                    ? \Carbon\Carbon::parse($nomination->declaration_date)->format('d M Y')
                                    : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Declaration Confirmed</small>

                            @if($nomination->confirmed_declaration)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nominees -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Nominees</h5>

                    <div>
                        <span class="badge bg-light text-dark">
                            {{ $nomination->nominees->count() }} Nominee{{ $nomination->nominees->count() == 1 ? '' : 's' }}
                        </span>

                        <span class="badge bg-{{ $allocationValid ? 'success' : 'danger' }}">
                            {{ number_format($totalPercentage, 2) }}%
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($nomination->nominees->isEmpty())
                        <div class="text-center text-muted p-4">
                            No nominees have been recorded.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nominee</th>
                                        <th>ID Number</th>
                                        <th>Relationship</th>
                                        <th>Contact</th>
                                        <th>DOB</th>
                                        <th>Minor</th>
                                        <th class="text-end">Allocation</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($nomination->nominees as $index => $nominee)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>

                                            <td>
                                                <strong>{{ $nominee->full_name }}</strong>

                                                @if($nominee->postal_address)
                                                    <div class="small text-muted">
                                                        {{ $nominee->postal_address }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>{{ $nominee->national_id }}</td>

                                            <td>{{ ucfirst(str_replace('_', ' ', $nominee->relationship)) }}</td>

                                            <td>
                                                <div>{{ $nominee->phone ?: '-' }}</div>

                                                @if($nominee->email)
                                                    <small class="text-muted">
                                                        {{ $nominee->email }}
                                                    </small>
                                                @endif
                                            </td>

                                            <td>
                                                {{ $nominee->date_of_birth
                                                    ? \Carbon\Carbon::parse($nominee->date_of_birth)->format('d M Y')
                                                    : '-' }}
                                            </td>

                                            <td>
                                                @if($nominee->is_minor)
                                                    <span class="badge bg-warning text-dark">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>

                                            <td class="text-end fw-bold">
                                                {{ number_format($nominee->percentage, 2) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="7" class="text-end">Total Allocation</th>
                                        <th class="text-end">
                                            <span class="badge bg-{{ $allocationValid ? 'success' : 'danger' }}">
                                                {{ number_format($totalPercentage, 2) }}%
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            @if(!$allocationValid)
                <div class="alert alert-danger shadow-sm mb-4">
                    <strong>Invalid beneficiary allocation.</strong>
                    Nominee percentages currently total {{ number_format($totalPercentage, 2) }}%.
                    The total must be exactly 100% before approval.
                </div>
            @endif

            <!-- Witnesses -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Witnesses</h5>
                </div>

                <div class="card-body">
                    @if($nomination->witnesses->isEmpty())
                        <div class="text-muted">
                            No witnesses have been recorded.
                        </div>
                    @else
                        <div class="row">
                            @foreach($nomination->witnesses as $index => $witness)
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">Witness {{ $index + 1 }}</h6>
                                            <span class="badge bg-secondary">{{ $witness->national_id }}</span>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block">Full Name</small>
                                            <strong>{{ $witness->full_name }}</strong>
                                        </div>

                                        <div>
                                            <small class="text-muted d-block mb-1">Signature</small>

                                            @if($witness->signature)
                                                <a href="{{ Storage::url($witness->signature) }}"
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Signature
                                                </a>
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Special Instructions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Special Instructions</h5>
                </div>

                <div class="card-body">
                    @if($nomination->special_instructions)
                        {!! nl2br(e($nomination->special_instructions)) !!}
                    @else
                        <span class="text-muted">No special instructions provided.</span>
                    @endif
                </div>
            </div>

            <!-- Declaration -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Member Declaration</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Declaration Confirmed</small>

                            @if($nomination->confirmed_declaration)
                                <span class="badge bg-success">Confirmed</span>
                            @else
                                <span class="badge bg-danger">Not Confirmed</span>
                            @endif
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Declaration Date</small>
                            <strong>
                                {{ $nomination->declaration_date
                                    ? \Carbon\Carbon::parse($nomination->declaration_date)->format('d M Y')
                                    : '-' }}
                            </strong>
                        </div>

                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Member Signature</small>

                            @if($nomination->member_signature)
                                <a href="{{ Storage::url($nomination->member_signature) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-primary mt-1">
                                    View Signature
                                </a>
                            @else
                                <span class="text-muted">Not provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">

            <!-- Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Nomination Summary</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst($nomination->status) }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Current Nomination</small>
                        @if($nomination->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Nominees</small>
                        <strong>{{ $nomination->nominees->count() }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Witnesses</small>
                        <strong>{{ $nomination->witnesses->count() }}</strong>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted d-block">Total Allocation</small>

                        <strong class="{{ $allocationValid ? 'text-success' : 'text-danger' }}">
                            {{ number_format($totalPercentage, 2) }}%
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Workflow -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Workflow</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Submitted By</small>
                        <strong>{{ $nomination->submittedBy->name ?? '-' }}</strong>

                        <div class="small text-muted">
                            {{ $nomination->submitted_at
                                ? \Carbon\Carbon::parse($nomination->submitted_at)->format('d M Y H:i')
                                : '-' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Approved By</small>
                        <strong>{{ $nomination->approvedBy->name ?? '-' }}</strong>

                        <div class="small text-muted">
                            {{ $nomination->approved_at
                                ? \Carbon\Carbon::parse($nomination->approved_at)->format('d M Y H:i')
                                : '-' }}
                        </div>
                    </div>

                    <div class="mb-0">
                        <small class="text-muted d-block">Rejected By</small>
                        <strong>{{ $nomination->rejectedBy->name ?? '-' }}</strong>

                        <div class="small text-muted">
                            {{ $nomination->rejected_at
                                ? \Carbon\Carbon::parse($nomination->rejected_at)->format('d M Y H:i')
                                : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            @if($nomination->status === 'rejected')
                <div class="card border-danger shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0">Rejection Reason</h6>
                    </div>

                    <div class="card-body">
                        {!! nl2br(e($nomination->rejection_reason ?: 'No rejection reason provided.')) !!}
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>

                <div class="card-body d-grid gap-2">
                    <a href="{{ route('nominations.index') }}" class="btn btn-light">
                        Back to Nominations
                    </a>

                    @if($nomination->status === 'pending')
                        <a href="{{ route('nominations.edit', $nomination->id) }}"
                           class="btn btn-outline-primary">
                            Edit Nomination
                        </a>

                        <button type="button"
                                class="btn btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#approveNominationModal"
                                {{ !$allocationValid || !$nomination->confirmed_declaration ? 'disabled' : '' }}>
                            Approve Nomination
                        </button>

                        <button type="button"
                                class="btn btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectNominationModal">
                            Reject Nomination
                        </button>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveNominationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST"
              action="{{ route('nominations.approve', $nomination->id) }}"
              class="modal-content"
              id="approveNominationForm">

            @csrf
            @method('PATCH')

            <div class="modal-header">
                <h5 class="modal-title">Approve Nomination</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p>
                    Confirm approval of this nomination for
                    <strong>{{ $nomination->member->full_name ?? 'the member' }}</strong>.
                </p>

                <div class="alert alert-success mb-0">
                    Beneficiary allocation:
                    <strong>{{ number_format($totalPercentage, 2) }}%</strong>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-success">
                    Approve Nomination
                </button>
            </div>

        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectNominationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST"
              action="{{ route('nominations.reject', $nomination->id) }}"
              class="modal-content"
              id="rejectNominationForm">

            @csrf
            @method('PATCH')

            <div class="modal-header">
                <h5 class="modal-title">Reject Nomination</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-0">
                    <label class="form-label">
                        Rejection Reason <span class="text-danger">*</span>
                    </label>

                    <textarea name="rejection_reason"
                              id="rejection_reason"
                              rows="5"
                              class="form-control"
                              required></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-danger">
                    Reject Nomination
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

@section('script')
<script>
$(function () {
    $('#rejectNominationForm').on('submit', function () {
        const reason = $('#rejection_reason').val().trim();

        if (!reason) {
            alert('Please provide a rejection reason.');
            return false;
        }

        return true;
    });

    $('#approveNominationForm').on('submit', function () {
        const allocationValid = {{ $allocationValid ? 'true' : 'false' }};
        const declarationConfirmed = {{ $nomination->confirmed_declaration ? 'true' : 'false' }};

        if (!allocationValid) {
            alert('Nominee allocation must total 100% before approval.');
            return false;
        }

        if (!declarationConfirmed) {
            alert('The member declaration must be confirmed before approval.');
            return false;
        }

        return true;
    });
});
</script>
@endsection