@extends('layouts.core')
@section('title', 'Nomination Management')

@section('content')
    @include('nominations.partial.header')
    <style>
        body {
            background: #f4f6f9;
        }

        .nomination-container {
            max-width: 1150px;
        }

        .form-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .form-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 18px 22px;
        }

        .form-card .card-body {
            padding: 24px;
        }

        .form-header {
            background: #198754;
            color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-right: 10px;
            border-radius: 50%;
            background: #198754;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .nominee-card,
        .witness-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .nominee-card .card-header,
        .witness-card .card-header {
            background: #f8f9fa;
            padding: 12px 16px;
        }

        .percentage-summary {
            position: sticky;
            top: 15px;
            z-index: 5;
        }

        .signature-preview {
            display: none;
            width: 100%;
            max-width: 220px;
            height: 120px;
            margin-top: 10px;
            padding: 4px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: contain;
            background: #ffffff;
        }

        .submit-section {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding: 15px 0;
            background: rgba(244, 246, 249, 0.95);
        }
    </style>
    <div class="container nomination-container py-2">
        <div class="form-header">
            {{-- <h2 class="mb-2">Member Nomination Form</h2> --}}
            <p class="mb-0">
                Nominate up to five beneficiaries and distribute the benefits so that the total allocation equals 100%.
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

        @php
            $oldNominees = old('nominees', [
                [
                    'full_name' => '',
                    'national_id' => '',
                    'postal_address' => '',
                    'phone' => '',
                    'email' => '',
                    'relationship' => '',
                    'is_minor' => 0,
                    'date_of_birth' => '',
                    'percentage' => ''
                ]
            ]);

            /*
             * The database may contain any number of nominees.
             * This particular paper-equivalent form limits entry to five.
             */
            $oldNominees = array_slice($oldNominees, 0, 5);

            $oldWitnesses = old('witnesses', [
                [
                    'full_name' => '',
                    'national_id' => ''
                ],
                [
                    'full_name' => '',
                    'national_id' => ''
                ]
            ]);
        @endphp

        <form
            id="nominationForm"
            action="{{ route('nominations.store') }}"
            method="POST"
            enctype="multipart/form-data"
            novalidate
        >
            @csrf

            <!-- Use the selected or authenticated member -->
            <input type="hidden" name="member_id" value="{{ $member->id }}">

            <!-- Preserve nomination history -->
            <input type="hidden" name="is_active" value="1">

            <!-- Member Details -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">1</span>
                        Member Details
                    </h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        Member details are obtained from the selected or logged-in member record.
                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Membership Number</label>

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $member->membership_number ?? $member->id }}"
                                readonly
                            >
                        </div>

                        <div class="col-md-6">
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

            <!-- Nominees -->
            <div class="card form-card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <h5 class="mb-0">
                        <span class="section-number">2</span>
                        Nominees
                    </h5>

                    <button
                        type="button"
                        id="addNomineeButton"
                        class="btn btn-outline-success btn-sm"
                    >
                        Add Nominee
                    </button>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-lg-9">
                            <div id="nomineesContainer">

                                @foreach ($oldNominees as $index => $nominee)
                                    <div
                                        class="card nominee-card nominee-row"
                                        data-index="{{ $index }}"
                                    >
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <strong>
                                                Nominee <span class="nominee-number">{{ $index + 1 }}</span>
                                            </strong>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger remove-nominee"
                                            >
                                                Remove
                                            </button>
                                        </div>

                                        <div class="card-body">
                                            <div class="row g-3">

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        Full Name
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="nominees[{{ $index }}][full_name]"
                                                        value="{{ $nominee['full_name'] ?? '' }}"
                                                        class="form-control nominee-field @error("nominees.$index.full_name") is-invalid @enderror"
                                                        data-field="full_name"
                                                        required
                                                    >

                                                    @error("nominees.$index.full_name")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        National ID Number
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="nominees[{{ $index }}][national_id]"
                                                        value="{{ $nominee['national_id'] ?? '' }}"
                                                        class="form-control nominee-field @error("nominees.$index.national_id") is-invalid @enderror"
                                                        data-field="national_id"
                                                        required
                                                    >

                                                    @error("nominees.$index.national_id")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        Postal Address
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="nominees[{{ $index }}][postal_address]"
                                                        value="{{ $nominee['postal_address'] ?? '' }}"
                                                        class="form-control nominee-field @error("nominees.$index.postal_address") is-invalid @enderror"
                                                        data-field="postal_address"
                                                        required
                                                    >

                                                    @error("nominees.$index.postal_address")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        Phone Number
                                                    </label>

                                                    <input
                                                        type="tel"
                                                        name="nominees[{{ $index }}][phone]"
                                                        value="{{ $nominee['phone'] ?? '' }}"
                                                        class="form-control nominee-field @error("nominees.$index.phone") is-invalid @enderror"
                                                        data-field="phone"
                                                        placeholder="+254 7XX XXX XXX"
                                                        required
                                                    >

                                                    @error("nominees.$index.phone")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">
                                                        Email Address
                                                    </label>

                                                    <input
                                                        type="email"
                                                        name="nominees[{{ $index }}][email]"
                                                        value="{{ $nominee['email'] ?? '' }}"
                                                        class="form-control nominee-field @error("nominees.$index.email") is-invalid @enderror"
                                                        data-field="email"
                                                    >

                                                    @error("nominees.$index.email")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        Relationship
                                                    </label>

                                                    <select
                                                        name="nominees[{{ $index }}][relationship]"
                                                        class="form-select nominee-field @error("nominees.$index.relationship") is-invalid @enderror"
                                                        data-field="relationship"
                                                        required
                                                    >
                                                        <option value="">Select relationship</option>

                                                        @foreach ([
                                                            'Spouse',
                                                            'Child',
                                                            'Parent',
                                                            'Sibling',
                                                            'Relative',
                                                            'Guardian',
                                                            'Other'
                                                        ] as $relationship)
                                                            <option
                                                                value="{{ $relationship }}"
                                                                @selected(($nominee['relationship'] ?? '') === $relationship)
                                                            >
                                                                {{ $relationship }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    @error("nominees.$index.relationship")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label required-label">
                                                        Allocation Percentage
                                                    </label>

                                                    <div class="input-group">
                                                        <input
                                                            type="number"
                                                            name="nominees[{{ $index }}][percentage]"
                                                            value="{{ $nominee['percentage'] ?? '' }}"
                                                            class="form-control nominee-field percentage-input @error("nominees.$index.percentage") is-invalid @enderror"
                                                            data-field="percentage"
                                                            min="0.01"
                                                            max="100"
                                                            step="0.01"
                                                            required
                                                        >

                                                        <span class="input-group-text">%</span>

                                                        @error("nominees.$index.percentage")
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6 d-flex align-items-end">
                                                    <div class="form-check form-switch mb-2">
                                                        <input
                                                            type="hidden"
                                                            name="nominees[{{ $index }}][is_minor]"
                                                            value="0"
                                                            class="minor-hidden"
                                                        >

                                                        <input
                                                            type="checkbox"
                                                            name="nominees[{{ $index }}][is_minor]"
                                                            value="1"
                                                            class="form-check-input minor-toggle"
                                                            id="minor_{{ $index }}"
                                                            @checked((bool) ($nominee['is_minor'] ?? false))
                                                        >

                                                        <label
                                                            class="form-check-label"
                                                            for="minor_{{ $index }}"
                                                        >
                                                            Nominee is under 18 years
                                                        </label>
                                                    </div>
                                                </div>

                                                <div
                                                    class="col-md-6 minor-date-container"
                                                    style="{{ !empty($nominee['is_minor']) ? '' : 'display:none;' }}"
                                                >
                                                    <label class="form-label required-label">
                                                        Date of Birth
                                                    </label>

                                                    <input
                                                        type="date"
                                                        name="nominees[{{ $index }}][date_of_birth]"
                                                        value="{{ $nominee['date_of_birth'] ?? '' }}"
                                                        class="form-control nominee-field nominee-date-of-birth @error("nominees.$index.date_of_birth") is-invalid @enderror"
                                                        data-field="date_of_birth"
                                                        max="{{ now()->toDateString() }}"
                                                        @required(!empty($nominee['is_minor']))
                                                    >

                                                    @error("nominees.$index.date_of_birth")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="percentage-summary">
                                <div id="percentageAlert" class="alert alert-warning">
                                    <h6>Allocation Summary</h6>

                                    <div class="d-flex justify-content-between">
                                        <span>Total allocated:</span>
                                        <strong>
                                            <span id="percentageTotal">0.00</span>%
                                        </strong>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <span>Remaining:</span>
                                        <strong>
                                            <span id="percentageRemaining">100.00</span>%
                                        </strong>
                                    </div>

                                    <hr>

                                    <small id="percentageMessage">
                                        Total nominee allocation must equal 100%.
                                    </small>
                                </div>

                                <div class="alert alert-light border">
                                    A maximum of five nominees can be entered using this form.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Special Instructions -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">3</span>
                        Special Instructions
                    </h5>
                </div>

                <div class="card-body">
                    <label for="special_instructions" class="form-label">
                        Special Instructions
                    </label>

                    <textarea
                        name="special_instructions"
                        id="special_instructions"
                        rows="4"
                        class="form-control @error('special_instructions') is-invalid @enderror"
                        placeholder="Enter any instructions relating to the nominated beneficiaries."
                    >{{ old('special_instructions') }}</textarea>

                    @error('special_instructions')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Witnesses -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">4</span>
                        Witnesses
                    </h5>
                </div>

                <div class="card-body">
                    <div class="row">

                        @for ($index = 0; $index < 2; $index++)
                            <div class="col-lg-6">
                                <div class="card witness-card">
                                    <div class="card-header">
                                        <strong>Witness {{ $index + 1 }}</strong>
                                    </div>

                                    <div class="card-body">
                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label class="form-label required-label">
                                                    Full Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="witnesses[{{ $index }}][full_name]"
                                                    value="{{ $oldWitnesses[$index]['full_name'] ?? '' }}"
                                                    class="form-control @error("witnesses.$index.full_name") is-invalid @enderror"
                                                    required
                                                >

                                                @error("witnesses.$index.full_name")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label required-label">
                                                    National ID Number
                                                </label>

                                                <input
                                                    type="text"
                                                    name="witnesses[{{ $index }}][national_id]"
                                                    value="{{ $oldWitnesses[$index]['national_id'] ?? '' }}"
                                                    class="form-control @error("witnesses.$index.national_id") is-invalid @enderror"
                                                    required
                                                >

                                                @error("witnesses.$index.national_id")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label required-label">
                                                    Witness Signature
                                                </label>

                                                <input
                                                    type="file"
                                                    name="witnesses[{{ $index }}][signature]"
                                                    accept=".jpg,.jpeg,.png,.pdf"
                                                    data-preview="#witnessSignaturePreview{{ $index }}"
                                                    class="form-control signature-input @error("witnesses.$index.signature") is-invalid @enderror"
                                                    required
                                                >

                                                <img
                                                    id="witnessSignaturePreview{{ $index }}"
                                                    class="signature-preview"
                                                    alt="Witness signature preview"
                                                >

                                                @error("witnesses.$index.signature")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor

                    </div>
                </div>
            </div>

            <!-- Declaration -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="section-number">5</span>
                        Member Declaration
                    </h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border">
                        I declare that the persons listed in this nomination are my chosen beneficiaries.
                        This nomination replaces any previous active nomination submitted by me.
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
                            <label for="member_signature" class="form-label required-label">
                                Member Signature
                            </label>

                            <input
                                type="file"
                                name="member_signature"
                                id="member_signature"
                                accept=".jpg,.jpeg,.png,.pdf"
                                data-preview="#memberSignaturePreview"
                                class="form-control signature-input @error('member_signature') is-invalid @enderror"
                                required
                            >

                            <img
                                id="memberSignaturePreview"
                                class="signature-preview"
                                alt="Member signature preview"
                            >

                            @error('member_signature')
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
                                    I confirm that the information provided is accurate and that the nominee
                                    percentages total 100%.
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Status should default to pending and should not be editable by members. -->

            <div class="submit-section">
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary px-4">
                        Clear Form
                    </button>

                    <button
                        type="submit"
                        id="submitButton"
                        class="btn btn-success px-5"
                    >
                        Submit Nomination
                    </button>
                </div>
            </div>

        </form>
    </div>
@stop

@section('script')
<script>
    $(document).ready(function () {
        const maximumNominees = 5;
        const maximumFileSize = 5 * 1024 * 1024;

        function nomineeTemplate(index) {
            return `
                <div class="card nominee-card nominee-row" data-index="${index}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>
                            Nominee <span class="nominee-number">${index + 1}</span>
                        </strong>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger remove-nominee"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][full_name]"
                                    class="form-control nominee-field"
                                    data-field="full_name"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    National ID Number
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][national_id]"
                                    class="form-control nominee-field"
                                    data-field="national_id"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Postal Address
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][postal_address]"
                                    class="form-control nominee-field"
                                    data-field="postal_address"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="nominees[${index}][phone]"
                                    class="form-control nominee-field"
                                    data-field="phone"
                                    placeholder="+254 7XX XXX XXX"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="nominees[${index}][email]"
                                    class="form-control nominee-field"
                                    data-field="email"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Relationship
                                </label>

                                <select
                                    name="nominees[${index}][relationship]"
                                    class="form-select nominee-field"
                                    data-field="relationship"
                                    required
                                >
                                    <option value="">Select relationship</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Child">Child</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Relative">Relative</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Allocation Percentage
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        name="nominees[${index}][percentage]"
                                        class="form-control nominee-field percentage-input"
                                        data-field="percentage"
                                        min="0.01"
                                        max="100"
                                        step="0.01"
                                        required
                                    >

                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input
                                        type="hidden"
                                        name="nominees[${index}][is_minor]"
                                        value="0"
                                        class="minor-hidden"
                                    >

                                    <input
                                        type="checkbox"
                                        name="nominees[${index}][is_minor]"
                                        value="1"
                                        class="form-check-input minor-toggle"
                                        id="minor_${index}"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="minor_${index}"
                                    >
                                        Nominee is under 18 years
                                    </label>
                                </div>
                            </div>

                            <div
                                class="col-md-6 minor-date-container"
                                style="display:none;"
                            >
                                <label class="form-label required-label">
                                    Date of Birth
                                </label>

                                <input
                                    type="date"
                                    name="nominees[${index}][date_of_birth]"
                                    class="form-control nominee-field nominee-date-of-birth"
                                    data-field="date_of_birth"
                                    max="{{ now()->toDateString() }}"
                                >
                            </div>

                        </div>
                    </div>
                </div>
            `;
        }

        function reindexNominees() {
            $('.nominee-row').each(function (index) {
                const row = $(this);

                row.attr('data-index', index);
                row.find('.nominee-number').text(index + 1);

                row.find('.nominee-field').each(function () {
                    const field = $(this).data('field');

                    $(this).attr(
                        'name',
                        `nominees[${index}][${field}]`
                    );
                });

                row.find('.minor-hidden').attr(
                    'name',
                    `nominees[${index}][is_minor]`
                );

                row.find('.minor-toggle')
                    .attr('name', `nominees[${index}][is_minor]`)
                    .attr('id', `minor_${index}`);

                row.find('.minor-toggle')
                    .next('label')
                    .attr('for', `minor_${index}`);
            });

            updateNomineeControls();
        }

        function updateNomineeControls() {
            const nomineeCount = $('.nominee-row').length;

            $('.remove-nominee').toggle(nomineeCount > 1);

            $('#addNomineeButton').prop(
                'disabled',
                nomineeCount >= maximumNominees
            );
        }

        function calculatePercentageTotal() {
            let total = 0;

            $('.percentage-input').each(function () {
                const value = parseFloat($(this).val());

                if (!isNaN(value)) {
                    total += value;
                }
            });

            total = Math.round(total * 100) / 100;

            const remaining = Math.round((100 - total) * 100) / 100;

            $('#percentageTotal').text(total.toFixed(2));
            $('#percentageRemaining').text(remaining.toFixed(2));

            const alert = $('#percentageAlert');
            const message = $('#percentageMessage');

            alert.removeClass(
                'alert-warning alert-success alert-danger'
            );

            if (Math.abs(total - 100) < 0.001) {
                alert.addClass('alert-success');
                message.text('The nominee allocation is complete.');
            } else if (total > 100) {
                alert.addClass('alert-danger');
                message.text('The total allocation exceeds 100%.');
            } else {
                alert.addClass('alert-warning');
                message.text('Total nominee allocation must equal 100%.');
            }

            return total;
        }

        $('#addNomineeButton').on('click', function () {
            const nomineeCount = $('.nominee-row').length;

            if (nomineeCount >= maximumNominees) {
                return;
            }

            $('#nomineesContainer').append(
                nomineeTemplate(nomineeCount)
            );

            reindexNominees();
            calculatePercentageTotal();
        });

        $(document).on('click', '.remove-nominee', function () {
            if ($('.nominee-row').length <= 1) {
                return;
            }

            $(this).closest('.nominee-row').remove();

            reindexNominees();
            calculatePercentageTotal();
        });

        $(document).on('input', '.percentage-input', function () {
            calculatePercentageTotal();
        });

        $(document).on('change', '.minor-toggle', function () {
            const row = $(this).closest('.nominee-row');
            const dateContainer = row.find('.minor-date-container');
            const dateInput = row.find('.nominee-date-of-birth');

            if ($(this).is(':checked')) {
                dateContainer.stop(true, true).slideDown();
                dateInput.prop('required', true);
            } else {
                dateContainer.stop(true, true).slideUp();
                dateInput.prop('required', false).val('');
            }
        });

        $('.signature-input').on('change', function () {
            const input = this;
            const file = input.files[0];
            const previewSelector = $(this).data('preview');

            $(this).removeClass('is-invalid');
            $(this).next('.client-file-error').remove();

            if (!file) {
                if (previewSelector) {
                    $(previewSelector).hide().attr('src', '');
                }

                return;
            }

            if (file.size > maximumFileSize) {
                $(this).val('').addClass('is-invalid');

                $('<div class="invalid-feedback client-file-error d-block">' +
                    'The selected file must not exceed 5 MB.' +
                    '</div>').insertAfter(this);

                if (previewSelector) {
                    $(previewSelector).hide().attr('src', '');
                }

                return;
            }

            if (previewSelector && file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    $(previewSelector)
                        .attr('src', event.target.result)
                        .fadeIn();
                };

                reader.readAsDataURL(file);
            } else if (previewSelector) {
                $(previewSelector).hide().attr('src', '');
            }
        });

        $('#nominationForm').on('submit', function (event) {
            const form = this;
            const percentageTotal = calculatePercentageTotal();

            if (Math.abs(percentageTotal - 100) >= 0.001) {
                event.preventDefault();
                event.stopPropagation();

                $('#percentageAlert')
                    .removeClass('alert-warning alert-success')
                    .addClass('alert-danger');

                $('#percentageMessage').text(
                    'You cannot submit the nomination until the total allocation equals 100%.'
                );

                $('html, body').animate({
                    scrollTop: $('#nomineesContainer').offset().top - 100
                }, 400);

                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                const firstInvalidField = $(form).find(':invalid').first();

                if (firstInvalidField.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 120
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

        $('#nominationForm').on('reset', function () {
            setTimeout(function () {
                while ($('.nominee-row').length > 1) {
                    $('.nominee-row').last().remove();
                }

                $('.minor-date-container').hide();
                $('.nominee-date-of-birth').prop('required', false);

                $('.signature-preview')
                    .hide()
                    .attr('src', '');

                $('.client-file-error').remove();
                $('.is-invalid').removeClass('is-invalid');

                $('#nominationForm').removeClass('was-validated');

                reindexNominees();
                calculatePercentageTotal();
            }, 0);
        });

        reindexNominees();
        calculatePercentageTotal();
    });
</script>
@endsection
