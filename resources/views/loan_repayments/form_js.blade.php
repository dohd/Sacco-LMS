<script>
$(function () {

    $('#loan_id').on('change', function () {

        const option = $(this).find(':selected');

        const memberId = option.data('member-id');
        const memberName = option.data('member-name');
        const balance = parseFloat(option.data('balance') || 0);

        $('#member_id').val(memberId || '');
        $('#member_display').val(memberName || '');

        $('#loan_balance').val(
            balance.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );
    });


    /*
     * Account credit repayments post to the savings control account, so the
     * cash / bank ledger account only applies to the other methods.
     */
    function toggleDepositAccount() {
        const isCredit = $('#payment_method').val() === 'account_credit';
        $('#depositAccountGroup').toggle(!isCredit);
        $('#deposit_account_id').prop('required', !isCredit);
        if (isCredit) {
            $('#deposit_account_id').val('');
        }
    }
    $('#payment_method').on('change', toggleDepositAccount);
    toggleDepositAccount();

    /*
     * Prevent accidental double submission.
     */
    $('form').on('submit', function () {

        const button = $('#saveBtn');

        if (button.data('submitted')) {
            return false;
        }

        button.data('submitted', true);

        button.prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin"></i> Saving...'
            );
    });

});
</script>