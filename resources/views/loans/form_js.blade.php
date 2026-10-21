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