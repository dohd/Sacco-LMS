@extends('layouts.core')

@section('title', 'Membership Management | View')
    
@section('content')
    @include('memberships.partial.header')
    <div class="container-fluid">
        <!-- Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                    <h4 class="mb-1">
                        {{ $application->application_number }}
                    </h4>

                    <div class="text-muted">
                        {{ trim($application->first_name . ' ' . $application->middle_name . ' ' . $application->last_name) }}
                    </div>
                </div>

                <div class="text-end">
                    @php
                        $statusClasses = [
                            'draft' => 'secondary',
                            'pending' => 'warning',
                            'under_review' => 'info',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'withdrawn' => 'dark',
                            'cancelled' => 'secondary',
                        ];

                        $statusClass = $statusClasses[$application->status] ?? 'secondary';
                    @endphp

                    <span class="badge bg-{{ $statusClass }} fs-6">
                        {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                    </span>

                    <div class="small text-muted mt-2">
                        Channel: {{ ucfirst($application->application_channel) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- Main Column -->
            <div class="col-lg-9">

                <!-- Personal Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Personal Details</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">First Name</small>
                                <strong>{{ $application->first_name }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Middle Name</small>
                                <strong>{{ $application->middle_name ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Last Name</small>
                                <strong>{{ $application->last_name }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Date of Birth</small>
                                <strong>{{ optional($application->date_of_birth)->format('d M Y') ?? $application->date_of_birth }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Place of Birth</small>
                                <strong>{{ $application->place_of_birth }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">National ID</small>
                                <strong>{{ $application->national_id }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Phone</small>
                                <strong>{{ $application->phone }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Email</small>
                                <strong>{{ $application->email ?: '-' }}</strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Current Address</small>
                                <div>{{ $application->current_address ?: '-' }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Residential Address</small>
                                <div>{{ $application->residential_address }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Employment Details</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Employer Name</small>
                                <strong>{{ $application->employer_name ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Working Station</small>
                                <strong>{{ $application->working_station ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Designation</small>
                                <strong>{{ $application->designation ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Employment Terms</small>
                                <strong>
                                    {{ $application->employment_terms
                                        ? ucfirst(str_replace('_', ' ', $application->employment_terms))
                                        : '-' }}
                                </strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Employer Phone</small>
                                <strong>{{ $application->employer_phone ?: '-' }}</strong>
                            </div>

                            <div class="col-md-8 mb-3">
                                <small class="text-muted d-block">Employer Address</small>
                                <div>{{ $application->employer_address ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Business Details</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Business Name</small>
                                <strong>{{ $application->business_name ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Nature of Business</small>
                                <strong>{{ $application->business_nature ?: '-' }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Business Phone</small>
                                <strong>{{ $application->business_phone ?: '-' }}</strong>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Business Address</small>
                                <div>{{ $application->business_address ?: '-' }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Business Location</small>
                                <div>{{ $application->business_location ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next of Kin -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Next of Kin</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Name</small>
                                <strong>{{ $application->next_of_kin_name }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">National ID</small>
                                <strong>{{ $application->next_of_kin_id }}</strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Relationship</small>
                                <strong>{{ $application->next_of_kin_relationship }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contribution -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Contribution Details</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Monthly Contribution</small>
                                <h5 class="mb-0">
                                    KES {{ number_format($application->monthly_contribution, 2) }}
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Contribution Start Date</small>
                                <strong>
                                    {{ optional($application->contribution_start_date)->format('d M Y')
                                        ?? $application->contribution_start_date }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Supporting Documents</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">

                            @php
                                $documents = [
                                    'national_id_front' => 'National ID Front',
                                    'national_id_back' => 'National ID Back',
                                    'passport_photo_1' => 'Passport Photo 1',
                                    'passport_photo_2' => 'Passport Photo 2',
                                    'nominee_form' => 'Nominee Form',
                                    'applicant_signature' => 'Applicant Signature',
                                ];
                            @endphp

                            @foreach($documents as $field => $label)
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <strong class="d-block mb-2">{{ $label }}</strong>

                                        @if($application->$field)
                                            <a href="{{ Storage::url($application->$field) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary">
                                                View Document
                                            </a>
                                        @else
                                            <span class="text-muted">Not uploaded</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>

                <!-- Declaration -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Declaration</h5>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Agreed to Terms</small>

                                @if($application->agreed_to_terms)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Application Date</small>
                                <strong>
                                    {{ $application->application_date
                                        ? \Carbon\Carbon::parse($application->application_date)->format('d M Y')
                                        : '-' }}
                                </strong>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-muted d-block">Submitted / Created</small>
                                <strong>{{ $application->created_at->format('d M Y H:i') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">

                <!-- Workflow -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Workflow</h5>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Status</small>

                            <span class="badge bg-{{ $statusClass }}">
                                {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Application Channel</small>
                            <strong>{{ ucfirst($application->application_channel) }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Reviewed At</small>
                            <strong>
                                {{ $application->reviewed_at
                                    ? \Carbon\Carbon::parse($application->reviewed_at)->format('d M Y H:i')
                                    : '-' }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Reviewed By</small>
                            <strong>{{ $application->reviewer->name ?? '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Approved At</small>
                            <strong>
                                {{ $application->approved_at
                                    ? \Carbon\Carbon::parse($application->approved_at)->format('d M Y H:i')
                                    : '-' }}
                            </strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Approved By</small>
                            <strong>{{ $application->approver->name ?? '-' }}</strong>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Rejected At</small>
                            <strong>
                                {{ $application->rejected_at
                                    ? \Carbon\Carbon::parse($application->rejected_at)->format('d M Y H:i')
                                    : '-' }}
                            </strong>
                        </div>

                        <div>
                            <small class="text-muted d-block">Rejected By</small>
                            <strong>{{ $application->rejector->name ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Review Notes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Review Notes</h6>
                    </div>

                    <div class="card-body">
                        {!! nl2br(e($application->review_notes ?: 'No review notes.')) !!}
                    </div>
                </div>

                @if($application->status === 'rejected')
                    <div class="card border-danger shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">Rejection Reason</h6>
                        </div>

                        <div class="card-body">
                            {!! nl2br(e($application->rejection_reason ?: 'No reason provided.')) !!}
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Actions</h6>
                    </div>

                    <div class="card-body d-grid gap-2">

                        <a href="{{ route('memberships.index') }}"
                           class="btn btn-light">
                            Back to Applications
                        </a>

                        @if(in_array($application->status, ['draft', 'pending']))
                            <a href="{{ route('memberships.edit', $application->id) }}"
                               class="btn btn-outline-primary">
                                Edit Application
                            </a>
                        @endif

                        @if($application->status === 'pending')
                            <form method="POST"
                                  action="{{ route('memberships.review', $application->id) }}">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="btn btn-info w-100">
                                    Mark Under Review
                                </button>
                            </form>
                        @endif

                        @if(in_array($application->status, ['pending', 'under_review']))
                            <button type="button"
                                    class="btn btn-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#approveModal">
                                Approve Application
                            </button>

                            <button type="button"
                                    class="btn btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal">
                                Reject Application
                            </button>
                        @endif

                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST"
                  action="{{ route('memberships.approve', $application->id) }}"
                  class="modal-content">

                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h5 class="modal-title">Approve Application</h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Approve membership application
                        <strong>{{ $application->application_number }}</strong>?
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Review Notes</label>

                        <textarea name="review_notes"
                                  rows="4"
                                  class="form-control">{{ $application->review_notes }}</textarea>
                    </div>

                    <div class="alert alert-warning mb-0">
                        Approval should create the permanent member record only once.
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
                        Approve Application
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST"
                  action="{{ route('memberships.reject', $application->id) }}"
                  class="modal-content">

                @csrf
                @method('PATCH')

                <div class="modal-header">
                    <h5 class="modal-title">Reject Application</h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">
                            Rejection Reason <span class="text-danger">*</span>
                        </label>

                        <textarea name="rejection_reason"
                                  rows="5"
                                  class="form-control"
                                  required></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Review Notes</label>

                        <textarea name="review_notes"
                                  rows="3"
                                  class="form-control">{{ $application->review_notes }}</textarea>
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
                        Reject Application
                    </button>
                </div>

            </form>
        </div>
    </div>
@stop
