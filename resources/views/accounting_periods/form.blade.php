{{-- =====================================================
     PERIOD DETAILS
====================================================== --}}
<div class="card shadow-sm mb-4">

    <div class="card-header bg-white">
        <h6 class="mb-0">
            Period Details
        </h6>
    </div>

    <div class="card-body">

        <div class="row g-3">

            {{-- Name --}}
            <div class="col-md-6">

                <label class="form-label">
                    Period Name
                    <span class="text-danger">*</span>
                </label>

                <input type="text"
                       name="name"
                       class="form-control"
                       maxlength="255"
                       required
                       placeholder="e.g. January 2027"
                       value="{{ old(
                           'name',
                           $isEdit
                               ? $accountingPeriod->name
                               : ''
                       ) }}">

            </div>


            {{-- Financial Year --}}
            <div class="col-md-6">

                <label class="form-label">
                    Financial Year
                    <span class="text-danger">*</span>
                </label>

                <input type="number"
                       name="financial_year"
                       id="financial_year"
                       class="form-control"
                       min="1900"
                       max="9999"
                       required
                       value="{{ old(
                           'financial_year',
                           $isEdit
                               ? $accountingPeriod->financial_year
                               : now()->year
                       ) }}">

            </div>


            {{-- Start Date --}}
            <div class="col-md-6">

                <label class="form-label">
                    Start Date
                    <span class="text-danger">*</span>
                </label>

                <input type="date"
                       name="start_date"
                       id="start_date"
                       class="form-control"
                       required
                       value="{{ old(
                           'start_date',
                           $isEdit
                               ? $accountingPeriod->start_date
                               : ''
                       ) }}">

            </div>


            {{-- End Date --}}
            <div class="col-md-6">

                <label class="form-label">
                    End Date
                    <span class="text-danger">*</span>
                </label>

                <input type="date"
                       name="end_date"
                       id="end_date"
                       class="form-control"
                       required
                       value="{{ old(
                           'end_date',
                           $isEdit
                               ? $accountingPeriod->end_date
                               : ''
                       ) }}">

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     PERIOD STATUS
====================================================== --}}
@if($isEdit)

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h6 class="mb-0">
                Period Status
            </h6>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">

                    <label class="form-label">
                        Current Status
                    </label>

                    <input type="text"
                           class="form-control"
                           value="{{ $statuses[$accountingPeriod->status] }}"
                           readonly>

                </div>

                <div class="col-md-6">

                    <label class="form-label">
                        Closed By
                    </label>

                    <input type="text"
                           class="form-control"
                           value="{{ optional($accountingPeriod->closedBy)->name ?? '—' }}"
                           readonly>

                </div>

            </div>

        </div>

    </div>

@endif


{{-- =====================================================
     ACCOUNTING CONTROL NOTICE
====================================================== --}}
<div class="alert alert-info mb-4">

    <div class="d-flex">

        <div class="me-3">
            <i class="fa fa-info-circle fa-lg"></i>
        </div>

        <div>

            <strong>Accounting Control</strong>

            <div class="small mt-1">
                A new accounting period is created as
                <strong>Open</strong>.
                Closing or locking a period should be performed
                through the dedicated accounting-period workflow.
            </div>

        </div>

    </div>

</div>


{{-- Actions --}}
<div class="d-flex justify-content-end gap-2 mb-5">

    <a href="{{ $isEdit
        ? route('accounting_periods.show', $accountingPeriod->id)
        : route('accounting_periods.index') }}"
       class="btn btn-light">
        Cancel
    </a>

    <button type="submit"
            class="btn btn-primary"
            id="saveBtn">

        <i class="fa fa-save"></i>

        {{ $isEdit
            ? 'Update Period'
            : 'Create Period' }}

    </button>

</div>