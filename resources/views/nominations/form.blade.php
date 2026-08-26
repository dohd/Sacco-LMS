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
<!-- Member Details -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">1</span>
            Member Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Member Name</label>

                <select
                    name="member_id"
                    class="form-select member_id @error("member_id") is-invalid @enderror"
                    data-field="member_id"
                    required
                >
                    <option value="">Select Member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected(old('member_id') === $member->id)>
                            {{ $member->membership_number }} - {{ $member->full_name }}
                        </option>
                    @endforeach
                </select>

                @error("member_id")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
                                        @if (empty($oldWitnesses[$index]['id'])) required @endif                                        
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
                    @if (empty(old('member_signature'))) required @endif
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