@php
    $isEdit = isset($loanRepayment);
    $isPending = !$isEdit || $loanRepayment->status === 'pending';

    $paymentMethods = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_money' => 'Mobile Money',
        'cheque' => 'Cheque',
        'standing_order' => 'Standing Order',
        'check_off' => 'Check Off',
        'post_dated_cheque' => 'Post Dated Cheque',
        'account_credit' => 'Account Credit',
    ];
@endphp

@if($isEdit && !$isPending)
    <div class="alert alert-warning">
        This repayment is <strong>{{ ucfirst($loanRepayment->status) }}</strong> and cannot be edited.
        Use the reversal workflow for confirmed repayments.
    </div>
@else
    {{-- =========================================================
         LOAN INFORMATION
    ========================================================== --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h6 class="mb-0">
                Loan Information
            </h6>
        </div>

        <div class="card-body">

            <div class="row g-3">

                {{-- Loan --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Loan Account
                        <span class="text-danger">*</span>
                    </label>

                    @if($isEdit)

                        <input type="hidden"
                               name="loan_id"
                               value="{{ $loanRepayment->loan_id }}">

                        <input type="text"
                               class="form-control"
                               value="{{ optional($loanRepayment->loan)->loan_number }}"
                               readonly>

                    @else

                        <select name="loan_id"
                                id="loan_id"
                                class="form-select"
                                required>

                            <option value="">
                                Select Loan
                            </option>

                            @foreach($loans ?? [] as $loan)

                                <option value="{{ $loan->id }}"
                                    data-member-id="{{ $loan->member_id }}"
                                    data-member-name="{{ optional($loan->member)->full_name }}"
                                    data-balance="{{ $loan->total_outstanding_balance }}"
                                    {{ old('loan_id') == $loan->id ? 'selected' : '' }}
                                    data-approved-amount="{{ $loan->approved_amount }}"
                                    >

                                    {{ $loan->loan_number }}
                                    -
                                    {{ optional($loan->member)->full_name }}
                                    -
                                    KES {{ number_format($loan->total_outstanding_balance ?: $loan->approved_amount, 2) }}

                                </option>

                            @endforeach

                        </select>

                    @endif

                </div>


                {{-- Member --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Member
                        <span class="text-danger">*</span>
                    </label>

                    @if($isEdit)

                        <input type="hidden"
                               name="member_id"
                               value="{{ $loanRepayment->member_id }}">

                        <input type="text"
                               id="member_display"
                               class="form-control"
                               value="{{ optional($loanRepayment->member)->full_name }}"
                               readonly>

                    @else

                        <input type="hidden"
                               name="member_id"
                               id="member_id"
                               value="{{ old('member_id') }}">

                        <input type="text"
                               id="member_display"
                               class="form-control"
                               placeholder="Member will appear here"
                               readonly>

                    @endif

                </div>


                {{-- Outstanding balance --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Current Outstanding Balance
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            KES
                        </span>

                        <input type="text"
                               id="loan_balance"
                               class="form-control"
                               value="{{ $isEdit
                                   ? number_format(optional($loanRepayment->loan)->total_outstanding_balance ?? 0, 2)
                                   : '' }}"
                               readonly>

                    </div>

                </div>


                {{-- Status --}}
                <div class="col-md-6">

                    <label class="form-label">
                        Status
                    </label>

                    <input type="text"
                           class="form-control"
                           value="{{ $isEdit ? ucfirst($loanRepayment->status) : 'Pending' }}"
                           readonly>

                </div>

            </div>

        </div>

    </div>



    {{-- =========================================================
         PAYMENT DETAILS
    ========================================================== --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h6 class="mb-0">
                Payment Details
            </h6>
        </div>

        <div class="card-body">

            <div class="row g-3">

                {{-- Amount --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Amount Paid
                        <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            KES
                        </span>

                        <input type="number"
                               name="amount_paid"
                               id="amount_paid"
                               class="form-control"
                               step="0.01"
                               min="0.01"
                               required
                               value="{{ old(
                                   'amount_paid',
                                   $isEdit
                                       ? $loanRepayment->amount_paid
                                       : ''
                               ) }}">

                    </div>

                </div>


                {{-- Payment date --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Payment Date
                        <span class="text-danger">*</span>
                    </label>

                    <input type="date"
                           name="payment_date"
                           class="form-control"
                           required
                           value="{{ old(
                               'payment_date',
                               $isEdit
                                   ? $loanRepayment->payment_date
                                   : now()->toDateString()
                           ) }}">

                </div>


                {{-- Value date --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Value Date
                    </label>

                    <input type="date"
                           name="value_date"
                           class="form-control"
                           value="{{ old(
                               'value_date',
                               $isEdit
                                   ? $loanRepayment->value_date
                                   : now()->toDateString()
                           ) }}">

                </div>


                {{-- Payment method --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Payment Method
                        <span class="text-danger">*</span>
                    </label>

                    <select name="payment_method"
                            id="payment_method"
                            class="form-select"
                            required>

                        <option value="">
                            Select Payment Method
                        </option>

                        @foreach($paymentMethods as $value => $label)

                            <option value="{{ $value }}"
                                {{ old(
                                    'payment_method',
                                    $isEdit
                                        ? $loanRepayment->payment_method
                                        : ''
                                ) == $value ? 'selected' : '' }}>

                                {{ $label }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Transaction reference --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Transaction Reference
                    </label>

                    <input type="text"
                           name="transaction_reference"
                           class="form-control"
                           maxlength="255"
                           placeholder="M-Pesa / bank / cheque reference"
                           value="{{ old(
                               'transaction_reference',
                               $isEdit
                                   ? $loanRepayment->transaction_reference
                                   : ''
                           ) }}">

                </div>


                {{-- Receipt --}}
                <div class="col-md-4">

                    <label class="form-label">
                        Receipt Number
                    </label>

                    <input type="text"
                           name="receipt_number"
                           class="form-control"
                           maxlength="255"
                           value="{{ old(
                               'receipt_number',
                               $isEdit
                                   ? $loanRepayment->receipt_number
                                   : ''
                           ) }}">

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         PAYER DETAILS
    ========================================================== --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h6 class="mb-0">
                Payer Details
            </h6>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Payer Name
                    </label>

                    <input type="text"
                           name="payer_name"
                           class="form-control"
                           maxlength="255"
                           value="{{ old(
                               'payer_name',
                               $isEdit
                                   ? $loanRepayment->payer_name
                                   : ''
                           ) }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Payer Phone
                    </label>

                    <input type="text"
                           name="payer_phone"
                           class="form-control"
                           maxlength="255"
                           value="{{ old(
                               'payer_phone',
                               $isEdit
                                   ? $loanRepayment->payer_phone
                                   : ''
                           ) }}">

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         SUPPORTING DOCUMENT
    ========================================================== --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header bg-white">
            <h6 class="mb-0">
                Supporting Information
            </h6>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Supporting Document
                    </label>

                    <input type="text"
                           name="supporting_document"
                           class="form-control"
                           maxlength="255"
                           placeholder="Document path / reference"
                           value="{{ old(
                               'supporting_document',
                               $isEdit
                                   ? $loanRepayment->supporting_document
                                   : ''
                           ) }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Remarks
                    </label>

                    <textarea name="remarks"
                              class="form-control"
                              rows="3">{{ old(
                                  'remarks',
                                  $isEdit
                                      ? $loanRepayment->remarks
                                      : ''
                              ) }}</textarea>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CONFIRMATION NOTICE
    ========================================================== --}}
    <div class="alert alert-info mb-4">

        <div class="d-flex">

            <div class="me-3">
                <i class="fa fa-info-circle fa-lg"></i>
            </div>

            <div>
                <strong>Repayment workflow</strong>

                <div class="small mt-1">
                    Saving this repayment will keep it
                    <strong>Pending</strong>.
                    No loan or repayment schedule balances will
                    change until the repayment is confirmed.
                </div>
            </div>

        </div>

    </div>


    {{-- Actions --}}
    <div class="d-flex justify-content-end gap-2 mb-5">

        <a href="{{ $isEdit
            ? route('loan_repayments.show', $loanRepayment->id)
            : route('loan_repayments.index') }}"
           class="btn btn-light">
            Cancel
        </a>

        <button type="submit"
                class="btn btn-primary"
                id="saveBtn">

            <i class="fa fa-save"></i>

            {{ $isEdit
                ? 'Update Repayment'
                : 'Save as Pending' }}

        </button>

    </div>
@endif
