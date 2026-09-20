<script>
$(function () {
    function updateCurrentBalance() {
        const option = $('#savings_account_id option:selected');
        const balance = parseFloat(option.data('balance')) || 0;

        $('#current_balance').val(balance.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));

        updatePreview();
    }

    function updateDirection() {
        const type = $('#transaction_type').val();
        let direction = '';

        if (['deposit', 'transfer_in'].includes(type)) {
            direction = 'credit';
        }

        if (['withdrawal', 'transfer_out'].includes(type)) {
            direction = 'debit';
        }

        if (type === 'adjustment') {
            direction = $('#direction').val() || 'credit';
        }

        $('#direction').val(direction);
        $('#direction_display').val(
            direction ? direction.charAt(0).toUpperCase() + direction.slice(1) : ''
        );

        $('#transferSection').toggle(
            ['transfer_in', 'transfer_out'].includes(type)
        );
        $('#destination_savings_account_id').val('');

        updatePreview();
    }

    function updatePreview() {
        const option = $('#savings_account_id option:selected');
        const current = parseFloat(option.data('balance')) || 0;
        const amount = parseFloat($('#amount').val()) || 0;
        const direction = $('#direction').val();

        let expected = current;

        if (direction === 'credit') {
            expected = current + amount;
        }

        if (direction === 'debit') {
            expected = current - amount;
        }

        $('#preview_current').text(current.toFixed(2));
        $('#preview_amount').text(amount.toFixed(2));
        $('#preview_balance').text(expected.toFixed(2));
    }

    $('#savings_account_id').on('change', updateCurrentBalance);
    $('#transaction_type').on('change', updateDirection);
    $('#amount').on('input', updatePreview);

    $('#savingsTransactionForm').on('submit', function () {
        const option = $('#savings_account_id option:selected');
        const current = parseFloat(option.data('balance')) || 0;
        const amount = parseFloat($('#amount').val()) || 0;
        const type = $('#transaction_type').val();

        if (amount <= 0) {
            alert('Transaction amount must be greater than zero.');
            return false;
        }

        if (['withdrawal', 'transfer_out'].includes(type) && amount > current) {
            alert('The transaction amount cannot exceed the available savings balance.');
            return false;
        }

        if (['transfer_in', 'transfer_out'].includes(type) && !$('#destination_savings_account_id').val()) {
            alert('Please select the destination savings account.');
            return false;
        }

        if ($('#destination_savings_account_id').val() === $('#savings_account_id').val()) {
            alert('Source and destination savings savingsAccount cannot be the same.');
            return false;
        }

        return true;
    });

    updateCurrentBalance();
    updateDirection();
});
</script>