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

    if ($('#loan_id').val()) {
        $('#loan_id').trigger('change');
    }

    function refreshEarlySettlementQuote() {
        const $early = $('#is_early_settlement');
        const $amount = $('#amount_paid');
        const $quote = $('#settlementQuote');
        const isEarly = $early.is(':checked');

        $amount.prop('readonly', isEarly);
        if (!isEarly) {
            $quote.addClass('d-none').empty();
            return;
        }

        const loanId = $('[name="loan_id"]').val();
        const paymentDate = $('#payment_date').val();
        $quote.removeClass('d-none').text('Calculating settlement amount...');
        if (!loanId || !paymentDate) {
            $quote.text('Select a loan and payment date to calculate the settlement amount.');
            return;
        }

        $.getJSON($early.data('quote-url'), { loan_id: loanId, payment_date: paymentDate })
            .done(function (response) {
                const quote = response.quote;
                $amount.val(Number(quote.settlement_amount).toFixed(2));
                $quote.html(
                    '<strong>Settlement amount: KES ' + Number(quote.settlement_amount).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>' +
                    '<div class="small mt-1">Principal KES ' + Number(quote.principal).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
                    ' | Interest due KES ' + Number(quote.interest_due).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
                    ' | Future interest waived KES ' + Number(quote.interest_waived).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
                    ' | Fees KES ' + Number(quote.fees).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
                    ' | Penalties KES ' + Number(quote.penalties).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</div>'
                );
            })
            .fail(function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Could not calculate the settlement. Check the loan and payment date.';
                $quote.text(message);
            });
    }

    $('#is_early_settlement').on('change', refreshEarlySettlementQuote);
    $('#payment_date').on('change', refreshEarlySettlementQuote);
    $('#loan_id').on('change', refreshEarlySettlementQuote);
    if ($('#is_early_settlement').is(':checked')) {
        refreshEarlySettlementQuote();
    }


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
