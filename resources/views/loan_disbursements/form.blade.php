<div class="card"><div class="card-body pt-3">
    <p>Loan: {{ $loanDisbursement->loan->loan_number }} | Approved payout: {{ number_format($loanDisbursement->net_amount, 2) }}</p>
    <label>Payment method</label>
    <select name="disbursement_method" class="form-select" required>
        @foreach(['account_credit', 'cash', 'bank_transfer', 'mobile_money', 'cheque'] as $method)
            <option value="{{ $method }}" {{ old('disbursement_method', $loanDisbursement->disbursement_method) === $method ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
        @endforeach
    </select>
    <div id="disbursementAccountGroup">
        <label class="mt-2">Cash / bank ledger account <span class="text-danger">*</span></label>
        <select name="disbursement_account_id" id="disbursement_account_id" class="form-select">
            <option value="">Select account the loan is paid from</option>
            @foreach($depositAccounts ?? [] as $account)
                <option value="{{ $account->id }}" {{ (string) old('disbursement_account_id', $loanDisbursement->disbursement_account_id) === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Not needed for account credit, which is paid from the member savings control account.</small>
    </div>
    @foreach(['transaction_reference' => 'Payment reference', 'bank_name' => 'Bank name', 'cheque_number' => 'Cheque number', 'payee_name' => 'Payee name', 'payee_phone' => 'Payee phone', 'payee_national_id' => 'Payee ID', 'supporting_document' => 'Supporting document reference'] as $field => $label)
        <label class="mt-2">{{ $label }}</label>
        <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $loanDisbursement->$field) }}">
    @endforeach
    <label class="mt-2">Payment date</label>
    <input type="date" name="disbursement_date" class="form-control" required value="{{ old('disbursement_date', \Carbon\Carbon::parse($loanDisbursement->disbursement_date)->format('Y-m-d')) }}">
    <label class="mt-2">Remarks</label>
    <textarea name="remarks" class="form-control">{{ old('remarks', $loanDisbursement->remarks) }}</textarea>
    <button class="btn btn-primary mt-3" type="submit">Save payment details</button>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var method = document.querySelector('select[name="disbursement_method"]');
    var group = document.getElementById('disbursementAccountGroup');
    var account = document.getElementById('disbursement_account_id');
    function toggle() {
        var credit = method.value === 'account_credit';
        group.style.display = credit ? 'none' : '';
        account.required = !credit;
        if (credit) { account.value = ''; }
    }
    method.addEventListener('change', toggle);
    toggle();
});
</script>
