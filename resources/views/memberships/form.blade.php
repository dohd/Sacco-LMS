<!-- Personal Details -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">1</span>
            Personal Details
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-4">
                <label for="last_name" class="form-label required-label">
                    Last Name
                </label>

                <input
                    type="text"
                    name="last_name"
                    id="last_name"
                    value="{{ old('last_name') }}"
                    class="form-control @error('last_name') is-invalid @enderror"
                    required
                >

                @error('last_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="middle_name" class="form-label">
                    Middle Name
                </label>

                <input
                    type="text"
                    name="middle_name"
                    id="middle_name"
                    value="{{ old('middle_name') }}"
                    class="form-control @error('middle_name') is-invalid @enderror"
                >

                @error('middle_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="first_name" class="form-label required-label">
                    First Name
                </label>

                <input
                    type="text"
                    name="first_name"
                    id="first_name"
                    value="{{ old('first_name') }}"
                    class="form-control @error('first_name') is-invalid @enderror"
                    required
                >

                @error('first_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="date_of_birth" class="form-label required-label">
                    Date of Birth
                </label>

                <input
                    type="date"
                    name="date_of_birth"
                    id="date_of_birth"
                    value="{{ old('date_of_birth')? dateFormat(old('date_of_birth'), 'Y-m-d') : '' }}"
                    max="{{ now()->toDateString() }}"
                    class="form-control @error('date_of_birth') is-invalid @enderror"
                    required
                >

                @error('date_of_birth')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="place_of_birth" class="form-label required-label">
                    Place of Birth
                </label>

                <input
                    type="text"
                    name="place_of_birth"
                    id="place_of_birth"
                    value="{{ old('place_of_birth') }}"
                    class="form-control @error('place_of_birth') is-invalid @enderror"
                    required
                >

                @error('place_of_birth')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="national_id" class="form-label required-label">
                    National ID Number
                </label>

                <input
                    type="text"
                    name="national_id"
                    id="national_id"
                    value="{{ old('national_id') }}"
                    class="form-control @error('national_id') is-invalid @enderror"
                    required
                >

                @error('national_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label required-label">
                    Phone Number
                </label>

                <input
                    type="tel"
                    name="phone"
                    id="phone"
                    value="{{ old('phone') }}"
                    placeholder="+254 7XX XXX XXX"
                    class="form-control @error('phone') is-invalid @enderror"
                    required
                >

                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="current_address" class="form-label">
                    Current Postal Address
                </label>

                <textarea
                    name="current_address"
                    id="current_address"
                    rows="2"
                    class="form-control @error('current_address') is-invalid @enderror"
                >{{ old('current_address') }}</textarea>

                @error('current_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="residential_address" class="form-label required-label">
                    Residential Address
                </label>

                <textarea
                    name="residential_address"
                    id="residential_address"
                    rows="3"
                    placeholder="Estate, road, building, house number, town or county"
                    class="form-control @error('residential_address') is-invalid @enderror"
                    required
                >{{ old('residential_address') }}</textarea>

                @error('residential_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Employment Details -->
<div class="card form-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <span class="section-number">2</span>
            Employment Details
        </h5>

        <div class="form-check form-switch">
            <input
                type="checkbox"
                class="form-check-input"
                id="isEmployed"
                @checked(
                    old('employer_name') ||
                    old('working_station') ||
                    old('designation')
                )
            >

            <label class="form-check-label" for="isEmployed">
                Currently employed
            </label>
        </div>
    </div>

    <div
        id="employmentSection"
        class="card-body optional-section"
    >
        <div class="row g-3">

            <div class="col-md-6">
                <label for="employer_name" class="form-label">
                    Employer Name
                </label>

                <input
                    type="text"
                    name="employer_name"
                    id="employer_name"
                    value="{{ old('employer_name') }}"
                    class="form-control @error('employer_name') is-invalid @enderror"
                >

                @error('employer_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="working_station" class="form-label">
                    Working Station
                </label>

                <input
                    type="text"
                    name="working_station"
                    id="working_station"
                    value="{{ old('working_station') }}"
                    class="form-control @error('working_station') is-invalid @enderror"
                >

                @error('working_station')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="designation" class="form-label">
                    Designation
                </label>

                <input
                    type="text"
                    name="designation"
                    id="designation"
                    value="{{ old('designation') }}"
                    class="form-control @error('designation') is-invalid @enderror"
                >

                @error('designation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="employment_terms" class="form-label">
                    Employment Terms
                </label>

                <select
                    name="employment_terms"
                    id="employment_terms"
                    class="form-select @error('employment_terms') is-invalid @enderror"
                >
                    <option value="">Select employment terms</option>
                    <option value="permanent"
                        @selected(old('employment_terms') === 'permanent')>
                        Permanent
                    </option>
                    <option value="contract"
                        @selected(old('employment_terms') === 'contract')>
                        Contract
                    </option>
                    <option value="temporary"
                        @selected(old('employment_terms') === 'temporary')>
                        Temporary
                    </option>
                    <option value="casual"
                        @selected(old('employment_terms') === 'casual')>
                        Casual
                    </option>
                    <option value="internship"
                        @selected(old('employment_terms') === 'internship')>
                        Internship
                    </option>
                    <option value="other"
                        @selected(old('employment_terms') === 'other')>
                        Other
                    </option>
                </select>

                @error('employment_terms')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="employer_phone" class="form-label">
                    Employer Phone Number
                </label>

                <input
                    type="tel"
                    name="employer_phone"
                    id="employer_phone"
                    value="{{ old('employer_phone') }}"
                    class="form-control @error('employer_phone') is-invalid @enderror"
                >

                @error('employer_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="employer_address" class="form-label">
                    Employer Address
                </label>

                <textarea
                    name="employer_address"
                    id="employer_address"
                    rows="2"
                    class="form-control @error('employer_address') is-invalid @enderror"
                >{{ old('employer_address') }}</textarea>

                @error('employer_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Business Details -->
<div class="card form-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <span class="section-number">3</span>
            Business Details
        </h5>

        <div class="form-check form-switch">
            <input
                type="checkbox"
                class="form-check-input"
                id="ownsBusiness"
                @checked(
                    old('business_name') ||
                    old('business_nature') ||
                    old('business_phone')
                )
            >

            <label class="form-check-label" for="ownsBusiness">
                Owns or operates a business
            </label>
        </div>
    </div>

    <div
        id="businessSection"
        class="card-body optional-section"
    >

        <div class="row g-3">

            <div class="col-md-6">
                <label for="business_name" class="form-label">
                    Business Name
                </label>

                <input
                    type="text"
                    name="business_name"
                    id="business_name"
                    value="{{ old('business_name') }}"
                    class="form-control @error('business_name') is-invalid @enderror"
                >

                @error('business_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="business_nature" class="form-label">
                    Nature of Business
                </label>

                <input
                    type="text"
                    name="business_nature"
                    id="business_nature"
                    value="{{ old('business_nature') }}"
                    placeholder="For example: Retail, construction or consultancy"
                    class="form-control @error('business_nature') is-invalid @enderror"
                >

                @error('business_nature')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="business_phone" class="form-label">
                    Business Phone Number
                </label>

                <input
                    type="tel"
                    name="business_phone"
                    id="business_phone"
                    value="{{ old('business_phone') }}"
                    class="form-control @error('business_phone') is-invalid @enderror"
                >

                @error('business_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="business_address" class="form-label">
                    Business Postal Address
                </label>

                <textarea
                    name="business_address"
                    id="business_address"
                    rows="2"
                    class="form-control @error('business_address') is-invalid @enderror"
                >{{ old('business_address') }}</textarea>

                @error('business_address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="business_location" class="form-label">
                    Physical Business Location
                </label>

                <textarea
                    name="business_location"
                    id="business_location"
                    rows="2"
                    placeholder="Building, street, town or county"
                    class="form-control @error('business_location') is-invalid @enderror"
                >{{ old('business_location') }}</textarea>

                @error('business_location')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Next of Kin -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">4</span>
            Next of Kin
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-4">
                <label for="next_of_kin_name" class="form-label required-label">
                    Full Name
                </label>

                <input
                    type="text"
                    name="next_of_kin_name"
                    id="next_of_kin_name"
                    value="{{ old('next_of_kin_name') }}"
                    class="form-control @error('next_of_kin_name') is-invalid @enderror"
                    required
                >

                @error('next_of_kin_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label for="next_of_kin_id" class="form-label required-label">
                    National ID Number
                </label>

                <input
                    type="text"
                    name="next_of_kin_id"
                    id="next_of_kin_id"
                    value="{{ old('next_of_kin_id') }}"
                    class="form-control @error('next_of_kin_id') is-invalid @enderror"
                    required
                >

                @error('next_of_kin_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label
                    for="next_of_kin_relationship"
                    class="form-label required-label"
                >
                    Relationship
                </label>

                <select
                    name="next_of_kin_relationship"
                    id="next_of_kin_relationship"
                    class="form-select @error('next_of_kin_relationship') is-invalid @enderror"
                    required
                >
                    <option value="">Select relationship</option>
                    <option value="spouse"
                        @selected(old('next_of_kin_relationship') === 'spouse')>
                        Spouse
                    </option>
                    <option value="parent"
                        @selected(old('next_of_kin_relationship') === 'parent')>
                        Parent
                    </option>
                    <option value="child"
                        @selected(old('next_of_kin_relationship') === 'child')>
                        Child
                    </option>
                    <option value="sibling"
                        @selected(old('next_of_kin_relationship') === 'sibling')>
                        Sibling
                    </option>
                    <option value="relative"
                        @selected(old('next_of_kin_relationship') === 'relative')>
                        Relative
                    </option>
                    <option value="guardian"
                        @selected(old('next_of_kin_relationship') === 'guardian')>
                        Guardian
                    </option>
                    <option value="other"
                        @selected(old('next_of_kin_relationship') === 'other')>
                        Other
                    </option>
                </select>

                @error('next_of_kin_relationship')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Contributions -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">5</span>
            Contributions
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label for="monthly_contribution" class="form-label required-label">
                    Monthly Contribution
                </label>

                <div class="input-group">
                    <span class="input-group-text">KES</span>

                    <input
                        type="number"
                        name="monthly_contribution"
                        id="monthly_contribution"
                        value="{{ old('monthly_contribution') }}"
                        min="0"
                        step="0.01"
                        class="form-control @error('monthly_contribution') is-invalid @enderror"
                        required
                    >

                    @error('monthly_contribution')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <label
                    for="contribution_start_date"
                    class="form-label required-label"
                >
                    Contribution Start Date
                </label>

                <input
                    type="date"
                    name="contribution_start_date"
                    id="contribution_start_date"
                    value="{{ old('contribution_start_date')? dateFormat(old('contribution_start_date'), 'Y-m-d') : '' }}"
                    class="form-control @error('contribution_start_date') is-invalid @enderror"
                    required
                >

                @error('contribution_start_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Supporting Documents -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">6</span>
            Supporting Documents
        </h5>
    </div>

    <div class="card-body">
        <div class="alert alert-info">
            Upload clear JPG, JPEG, PNG or PDF files. The recommended maximum size is 5 MB per file.
        </div>

        <div class="row g-4">

            <div class="col-md-6">
                <label for="national_id_front" class="form-label">
                    National ID – Front
                </label>

                <input
                    type="file"
                    name="national_id_front"
                    id="national_id_front"
                    accept=".jpg,.jpeg,.png,.pdf"
                    data-preview="#nationalIdFrontPreview"
                    class="form-control document-input @error('national_id_front') is-invalid @enderror"
                >

                <img
                    id="nationalIdFrontPreview"
                    class="document-preview"
                    alt="National ID front preview"
                >

                @error('national_id_front')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="national_id_back" class="form-label">
                    National ID – Back
                </label>

                <input
                    type="file"
                    name="national_id_back"
                    id="national_id_back"
                    accept=".jpg,.jpeg,.png,.pdf"
                    data-preview="#nationalIdBackPreview"
                    class="form-control document-input @error('national_id_back') is-invalid @enderror"
                >

                <img
                    id="nationalIdBackPreview"
                    class="document-preview"
                    alt="National ID back preview"
                >

                @error('national_id_back')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="passport_photo_1" class="form-label">
                    Passport Photo 1
                </label>

                <input
                    type="file"
                    name="passport_photo_1"
                    id="passport_photo_1"
                    accept=".jpg,.jpeg,.png"
                    data-preview="#passportPhoto1Preview"
                    class="form-control document-input @error('passport_photo_1') is-invalid @enderror"
                >

                <img
                    id="passportPhoto1Preview"
                    class="document-preview"
                    alt="Passport photo 1 preview"
                >

                @error('passport_photo_1')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="passport_photo_2" class="form-label">
                    Passport Photo 2
                </label>

                <input
                    type="file"
                    name="passport_photo_2"
                    id="passport_photo_2"
                    accept=".jpg,.jpeg,.png"
                    data-preview="#passportPhoto2Preview"
                    class="form-control document-input @error('passport_photo_2') is-invalid @enderror"
                >

                <img
                    id="passportPhoto2Preview"
                    class="document-preview"
                    alt="Passport photo 2 preview"
                >

                @error('passport_photo_2')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="nominee_form" class="form-label">
                    Nominee Form
                </label>

                <input
                    type="file"
                    name="nominee_form"
                    id="nominee_form"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="form-control document-input @error('nominee_form') is-invalid @enderror"
                >

                @error('nominee_form')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="applicant_signature" class="form-label">
                    Applicant Signature
                </label>

                <input
                    type="file"
                    name="applicant_signature"
                    id="applicant_signature"
                    accept=".jpg,.jpeg,.png"
                    data-preview="#signaturePreview"
                    class="form-control document-input @error('applicant_signature') is-invalid @enderror"
                >

                <img
                    id="signaturePreview"
                    class="document-preview"
                    alt="Applicant signature preview"
                >

                @error('applicant_signature')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

<!-- Declaration -->
<div class="card form-card">
    <div class="card-header">
        <h5 class="mb-0">
            <span class="section-number">7</span>
            Declaration
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label for="application_date" class="form-label">
                    Application Date
                </label>

                <input
                    type="date"
                    name="application_date"
                    id="application_date"
                    value="{{ old('application_date')? dateFormat(old('application_date'), 'Y-m-d') : now()->toDateString() }}"
                    max="{{ now()->toDateString() }}"
                    class="form-control @error('application_date') is-invalid @enderror"
                >

                @error('application_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="border rounded p-3 bg-light">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            name="agreed_to_terms"
                            id="agreed_to_terms"
                            value="1"
                            class="form-check-input @error('agreed_to_terms') is-invalid @enderror"
                            @checked(old('agreed_to_terms'))
                        >

                        <label
                            for="agreed_to_terms"
                            class="form-check-label required-label"
                        >
                            I declare that the information provided in this application is true
                            and complete. I agree to comply with the applicable membership terms,
                            contribution requirements, policies and regulations.
                        </label>

                        @error('agreed_to_terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Workflow status is intentionally not editable by the applicant.
     Let the database use its default value: pending. -->

<div class="submit-section">
    <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
        {{ Form::hidden('submission_action', old('submission_action', 'draft'), ['id' => 'submission_action']) }} 

        <button type="reset" class="btn btn-outline-secondary px-4">
            Clear Form
        </button>

        <button type="submit" id="submitButton" class="btn btn-success px-5">
            Submit Application
        </button>
    </div>
</div>