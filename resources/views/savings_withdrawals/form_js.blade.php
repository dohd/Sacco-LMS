<script>
$(document).ready(function () {

    /*
     * Hide all conditional payment fields.
     */
    $('.payment-field').hide();


    /*
     * Format currency.
     */
    function formatCurrency(value) {

        value = parseFloat(value) || 0;

        return 'KES ' + value.toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }


    /*
     * Update account information.
     */
    function updateAccountInformation() {

        var selected = $('#savings_account_id option:selected');

        var balance = parseFloat(selected.data('balance')) || 0;
        var product = selected.data('product') || '—';

        $('#available_balance').text(formatCurrency(balance));
        $('#summaryBalance').text(formatCurrency(balance));
        $('#savings_product').text(product);

        updateSummary();

    }


    /*
     * Update withdrawal summary.
     */
    function updateSummary() {

        var balance = parseFloat(
            $('#savings_account_id option:selected').data('balance')
        ) || 0;

        var amount = parseFloat($('#amount').val()) || 0;

        var remaining = balance - amount;

        $('#summaryAmount').text(formatCurrency(amount));

        if (remaining < 0) {

            $('#summaryRemaining')
                .text(formatCurrency(remaining))
                .removeClass('text-success')
                .addClass('text-danger');

            $('#balanceWarning')
                .removeClass('d-none')
                .text(
                    'Withdrawal amount exceeds the available balance.'
                );

            $('#amount').addClass('is-invalid');

            $('#submitBtn').prop('disabled', true);

        } else {

            $('#summaryRemaining')
                .text(formatCurrency(remaining))
                .removeClass('text-danger')
                .addClass('text-success');

            $('#balanceWarning')
                .addClass('d-none')
                .text('');

            $('#amount').removeClass('is-invalid');

            $('#submitBtn').prop('disabled', false);
        }

    }


    /*
     * Account selection.
     */
    $('#savings_account_id').on('change', function () {
        updateAccountInformation();
    });


    /*
     * Amount changes.
     */
    $('#amount').on('input change', function () {
        updateSummary();
    });


    /*
     * Payment method.
     */
    $('#payment_method').on('change', function () {

        var method = $(this).val();

        $('.payment-field').hide();

        /*
         * Clear conditional fields.
         */
        $('#mobile_money_number').prop('required', false);
        $('#bank_account_number').prop('required', false);
        $('#bank_name').prop('required', false);
        $('#cheque_number').prop('required', false);

        if (method === 'mobile_money') {

            $('#mobileMoneyField').show();

            $('#mobile_money_number')
                .prop('required', true);

        }

        if (method === 'bank_transfer') {

            $('#bankAccountField').show();
            $('#bankNameField').show();

            $('#bank_account_number')
                .prop('required', true);

            $('#bank_name')
                .prop('required', true);

        }

        if (method === 'cheque') {

            $('#chequeField').show();

            $('#cheque_number')
                .prop('required', true);

        }

    });


    /*
     * Form submission.
     */
    $('#withdrawalForm').on('submit', function (e) {

        var balance = parseFloat(
            $('#savings_account_id option:selected').data('balance')
        ) || 0;

        var amount = parseFloat($('#amount').val()) || 0;

        if (!$('#savings_account_id').val()) {

            e.preventDefault();

            alert('Please select a savings account.');

            return;
        }

        if (amount <= 0) {

            e.preventDefault();

            alert('Please enter a valid withdrawal amount.');

            return;
        }

        if (amount > balance) {

            e.preventDefault();

            alert('Withdrawal amount exceeds the available savings balance.');

            return;
        }

        $('#submitBtn')
            .prop('disabled', true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1"></span>' +
                'Submitting...'
            );

    });


    /*
     * Initialize existing values after validation error.
     */
    updateAccountInformation();

    $('#payment_method').trigger('change');

});
</script>