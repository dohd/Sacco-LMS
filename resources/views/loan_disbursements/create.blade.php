@extends('layouts.core')
@section('title', 'Loan Disbursements')

@section('content')
    @include('loan_disbursements.partial.header')
        <div class="container-fluid">
        <form method="POST"
              action="{{ isset($disbursement) ? route('loan_disbursements.update', $disbursement->id) : route('loan_disbursements.store') }}"
              enctype="multipart/form-data"
              id="loanDisbursementForm">

            @csrf
            @if(isset($disbursement))
                @method('PUT')
            @endif

            <!-- Basic Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Loan Disbursement</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Disbursement No.</label>
                            <input type="text"
                                   name="disbursement_number"
                                   class="form-control"
                                   value="{{ old('disbursement_number', $disbursement->disbursement_number ?? $disbursementNumber ?? '') }}"
                                   readonly>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label class="form-label">Loan Application <span class="text-danger">*</span></label>
                            <select name="loan_application_id" class="form-select" required>
                                <option value="">Select Application</option>
                                @foreach($loanApplications as $application)
                                    <option value="{{ $application->id }}"
                                        {{ old('loan_application_id', $disbursement->loan_application_id ?? '') == $application->id ? 'selected' : '' }}>
                                        {{ $application->application_number }}
                                        - {{ $application->member->full_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Loan Account</label>
                            <select name="loan_id" class="form-select">
                                <option value="">Select Loan</option>
                                @foreach($loans as $loan)
                                    <option value="{{ $loan->id }}"
                                        {{ old('loan_id', $disbursement->loan_id ?? '') == $loan->id ? 'selected' : '' }}>
                                        {{ $loan->loan_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Amount Breakdown</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gross Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="gross_amount"
                                       id="gross_amount"
                                       class="form-control text-end"
                                       value="{{ old('gross_amount', $disbursement->gross_amount ?? '') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Deductions</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="deductions_amount"
                                       id="deductions_amount"
                                       class="form-control text-end"
                                       value="{{ old('deductions_amount', $disbursement->deductions_amount ?? 0) }}">
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Net Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number"
                                       step="0.01"
                                       name="net_amount"
                                       id="net_amount"
                                       class="form-control text-end bg-light"
                                       value="{{ old('net_amount', $disbursement->net_amount ?? '') }}"
                                       readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disbursement Method -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payment Method</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Disbursement Method <span class="text-danger">*</span></label>
                            <select name="disbursement_method"
                                    id="disbursement_method"
                                    class="form-select"
                                    required>
                                <option value="">Select Method</option>
                                <option value="bank_transfer" {{ old('disbursement_method', $disbursement->disbursement_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="mobile_money" {{ old('disbursement_method', $disbursement->disbursement_method ?? '') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                <option value="cheque" {{ old('disbursement_method', $disbursement->disbursement_method ?? '') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="cash" {{ old('disbursement_method', $disbursement->disbursement_method ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="account_credit" {{ old('disbursement_method', $disbursement->disbursement_method ?? '') == 'account_credit' ? 'selected' : '' }}>Account Credit</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text"
                                   name="transaction_reference"
                                   class="form-control"
                                   value="{{ old('transaction_reference', $disbursement->transaction_reference ?? '') }}">
                        </div>
                    </div>

                    <!-- Cheque Fields -->
                    <div id="chequeFields">
                        <hr>
                        <h6 class="mb-3">Cheque Details</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cheque Number</label>
                                <input type="text"
                                       name="cheque_number"
                                       class="form-control"
                                       value="{{ old('cheque_number', $disbursement->cheque_number ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cheque Date</label>
                                <input type="date"
                                       name="cheque_date"
                                       class="form-control"
                                       value="{{ old('cheque_date', isset($disbursement->cheque_date) ? optional($disbursement->cheque_date)->format('Y-m-d') : '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text"
                                       name="bank_name"
                                       class="form-control"
                                       value="{{ old('bank_name', $disbursement->bank_name ?? '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payee / Collection -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Payee & Collection Details</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payee / Collector Name</label>
                            <input type="text"
                                   name="payee_name"
                                   class="form-control"
                                   value="{{ old('payee_name', $disbursement->payee_name ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">National ID</label>
                            <input type="text"
                                   name="payee_national_id"
                                   class="form-control"
                                   value="{{ old('payee_national_id', $disbursement->payee_national_id ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text"
                                   name="payee_phone"
                                   class="form-control"
                                   value="{{ old('payee_phone', $disbursement->payee_phone ?? '') }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Disbursement Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="disbursement_date"
                                   class="form-control"
                                   value="{{ old('disbursement_date', isset($disbursement->disbursement_date) ? optional($disbursement->disbursement_date)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Value Date</label>
                            <input type="date"
                                   name="value_date"
                                   class="form-control"
                                   value="{{ old('value_date', isset($disbursement->value_date) ? optional($disbursement->value_date)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Collection Date</label>
                            <input type="date"
                                   name="collection_date"
                                   class="form-control"
                                   value="{{ old('collection_date', isset($disbursement->collection_date) ? optional($disbursement->collection_date)->format('Y-m-d') : '') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Postal Reference</label>
                            <input type="text"
                                   name="postal_reference"
                                   class="form-control"
                                   value="{{ old('postal_reference', $disbursement->postal_reference ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supporting Documents -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Supporting Information</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supporting Document</label>
                            <input type="file"
                                   name="supporting_document"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png">

                            @if(!empty($disbursement->supporting_document))
                                <small class="text-muted">
                                    Current file: {{ basename($disbursement->supporting_document) }}
                                </small>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks"
                                      rows="3"
                                      class="form-control">{{ old('remarks', $disbursement->remarks ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workflow -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Workflow</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ ucfirst(str_replace('_', ' ', $disbursement->status ?? 'draft')) }}"
                                   readonly>
                        </div>

                        @if(!empty($disbursement->approved_at))
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Approved At</label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="{{ $disbursement->approved_at }}"
                                       readonly>
                            </div>
                        @endif

                        @if(!empty($disbursement->processed_at))
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Processed At</label>
                                <input type="text"
                                       class="form-control bg-light"
                                       value="{{ $disbursement->processed_at }}"
                                       readonly>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('loan_disbursements.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <div>
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                            Save Draft
                        </button>

                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                            Submit for Approval
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>    
@stop

@section('script')
<script>
$(function () {
    function calculateNetAmount() {
        const gross = parseFloat($('#gross_amount').val()) || 0;
        const deductions = parseFloat($('#deductions_amount').val()) || 0;
        $('#net_amount').val(Math.max(gross - deductions, 0).toFixed(2));
    }

    function toggleMethodFields() {
        const method = $('#disbursement_method').val();
        $('#chequeFields').toggle(method === 'cheque');
    }

    $('#gross_amount, #deductions_amount').on('input', calculateNetAmount);
    $('#disbursement_method').on('change', toggleMethodFields);

    calculateNetAmount();
    toggleMethodFields();

    $('#loanDisbursementForm').on('submit', function () {
        const gross = parseFloat($('#gross_amount').val()) || 0;
        const deductions = parseFloat($('#deductions_amount').val()) || 0;

        if (deductions > gross) {
            alert('Deductions cannot exceed the gross disbursement amount.');
            return false;
        }

        if ($('#disbursement_method').val() === 'cheque') {
            const chequeNumber = $('input[name="cheque_number"]').val();
            const chequeDate = $('input[name="cheque_date"]').val();

            if (!chequeNumber || !chequeDate) {
                alert('Cheque number and cheque date are required for cheque disbursements.');
                return false;
            }
        }

        return true;
    });
});
</script>
@endsection
