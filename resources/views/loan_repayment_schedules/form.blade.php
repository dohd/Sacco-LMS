<div class="row g-4">

    {{-- LEFT --}}
    <div class="col-xl-8">

        {{-- LOAN INFORMATION --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-file-invoice-dollar text-primary me-2"></i>
                    Loan Information
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Loan Account
                            <span class="text-danger">*</span>
                        </label>

                        <select name="loan_id"
                                id="loan_id"
                                class="form-select @error('loan_id') is-invalid @enderror"
                                required>

                            <option value="">Select loan account</option>

                            @foreach($loans ?? [] as $loan)

                                <option value="{{ $loan->id }}"
                                    {{ $loanId == $loan->id ? 'selected' : '' }}>

                                    {{ $loan->loan_number }}
                                    @if(isset($loan->member))
                                        — {{ $loan->member->name }}
                                    @endif

                                </option>

                            @endforeach

                        </select>

                        @error('loan_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-6">
                        <label class="form-label">
                            Member
                            <span class="text-danger">*</span>
                        </label>
                        <select name="member_id"
                                id="member_id"
                                class="form-select @error('member_id') is-invalid @enderror"
                                required>
                            <option value="">Select member</option>
                            @foreach($members ?? [] as $member)
                                <option value="{{ $member->id }}"
                                    {{ $memberId == $member->id ? 'selected' : '' }}>
                                    {{ $member->membership_number }} - {{ $member->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('member_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Installment Number
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               name="installment_number"
                               min="1"
                               value="{{ old('installment_number', $schedule->installment_number ?? '') }}"
                               class="form-control @error('installment_number') is-invalid @enderror"
                               required>

                        @error('installment_number')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Due Date
                            <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="due_date"
                               value="{{ old('due_date', isset($schedule) ? $schedule->due_date : '') }}"
                               class="form-control @error('due_date') is-invalid @enderror"
                               required>

                        @error('due_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>
        </div>


        {{-- SCHEDULED AMOUNTS --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-calculator text-primary me-2"></i>
                    Scheduled Repayment
                </h6>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Opening Principal Balance
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="opening_principal_balance"
                               id="opening_principal_balance"
                               value="{{ old('opening_principal_balance', $schedule->opening_principal_balance ?? '') }}"
                               class="form-control amount @error('opening_principal_balance') is-invalid @enderror"
                               required>

                        @error('opening_principal_balance')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Principal Due
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="principal_due"
                               id="principal_due"
                               value="{{ old('principal_due', $schedule->principal_due ?? '') }}"
                               class="form-control amount @error('principal_due') is-invalid @enderror"
                               required>

                        @error('principal_due')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Interest Due
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="interest_due"
                               id="interest_due"
                               value="{{ old('interest_due', $schedule->interest_due ?? 0) }}"
                               class="form-control amount">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Fees Due
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="fees_due"
                               id="fees_due"
                               value="{{ old('fees_due', $schedule->fees_due ?? 0) }}"
                               class="form-control amount">

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Penalty Due
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               name="penalty_due"
                               id="penalty_due"
                               value="{{ old('penalty_due', $schedule->penalty_due ?? 0) }}"
                               class="form-control amount">

                    </div>


                    {{-- TOTAL --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Total Due
                        </label>

                        <input type="number"
                               step="0.01"
                               name="total_due"
                               id="total_due"
                               value="{{ old('total_due', $schedule->total_due ?? 0) }}"
                               class="form-control bg-light fw-bold"
                               readonly>

                    </div>


                    {{-- CLOSING PRINCIPAL --}}
                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Closing Principal Balance
                        </label>

                        <input type="number"
                               step="0.01"
                               name="closing_principal_balance"
                               id="closing_principal_balance"
                               value="{{ old('closing_principal_balance', $schedule->closing_principal_balance ?? 0) }}"
                               class="form-control bg-light fw-bold"
                               readonly>

                    </div>

                </div>

            </div>
        </div>


        {{-- REMARKS --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-comment-alt text-primary me-2"></i>
                    Remarks
                </h6>
            </div>

            <div class="card-body">

                <textarea name="remarks"
                          rows="4"
                          class="form-control"
                          placeholder="Optional remarks...">{{ old('remarks', $schedule->remarks ?? '') }}</textarea>

            </div>

        </div>

    </div>


    {{-- RIGHT SIDEBAR --}}
    <div class="col-xl-4">

        {{-- STATUS --}}
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fa fa-tasks text-primary me-2"></i>
                    Schedule Status
                </h6>
            </div>

            <div class="card-body">

                @if($isEdit)

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            id="status"
                            class="form-select">

                        @foreach([
                            'pending' => 'Pending',
                            'partially_paid' => 'Partially Paid',
                            'paid' => 'Paid',
                            'overdue' => 'Overdue',
                            'waived' => 'Waived',
                        ] as $key => $label)

                            <option value="{{ $key }}"
                                {{ old('status', $schedule->status) === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                    <div class="form-text">
                        Payment statuses should normally be
                        controlled by the repayment allocation workflow.
                    </div>

                @else

                    <input type="hidden"
                           name="status"
                           value="pending">

                    <span class="badge bg-warning text-dark px-3 py-2">
                        Pending
                    </span>

                @endif

            </div>
        </div>


        {{-- PAYMENT SUMMARY --}}
        @if($isEdit)

            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-money-bill-wave text-primary me-2"></i>
                        Payment Summary
                    </h6>
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Principal Paid
                        </span>

                        <strong>
                            {{ number_format($schedule->principal_paid, 2) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Interest Paid
                        </span>

                        <strong>
                            {{ number_format($schedule->interest_paid, 2) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Fees Paid
                        </span>

                        <strong>
                            {{ number_format($schedule->fees_paid, 2) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Penalty Paid
                        </span>

                        <strong>
                            {{ number_format($schedule->penalty_paid, 2) }}
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">
                            Total Paid
                        </span>

                        <strong class="text-success">
                            {{ number_format($schedule->total_paid, 2) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">
                            Outstanding
                        </span>

                        <strong class="text-danger">
                            {{ number_format($schedule->outstanding_amount, 2) }}
                        </strong>
                    </div>

                    @if($schedule->fully_paid_date)

                        <hr>

                        <div class="small text-muted">
                            Fully Paid Date
                        </div>

                        <div class="fw-semibold">
                            {{ $schedule->fully_paid_date }}
                        </div>

                    @endif

                </div>
            </div>

        @endif


        {{-- ACTIONS --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <button type="submit"
                        class="btn btn-primary w-100"
                        id="saveBtn">
                    <i class="fa fa-save me-1"></i>
                    {{ $isEdit ? 'Update Schedule' : 'Save Schedule' }}
                </button>
                <a href="{{ route('loan_repayment_schedules.index') }}"
                   class="btn btn-outline-secondary w-100 mt-2">
                    Cancel
                </a>
            </div>
        </div>

    </div>

</div>
