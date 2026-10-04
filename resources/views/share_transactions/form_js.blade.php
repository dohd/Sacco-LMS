<script>
$(document).ready(function () {

    const creditTypes = ['purchase', 'transfer_in', 'bonus_issue'];
    const debitTypes = ['transfer_out', 'redemption'];

    function selectedAccount() {
        return $('#share_account_id option:selected');
    }

    function updateAccountSnapshot() {
        const option = selectedAccount();

        const member = option.data('member') || '—';
        const accountNumber = option.data('account-number') || '—';
        const units = parseInt(option.data('running-units')) || 0;
        const balance = parseFloat(option.data('running-balance')) || 0;
        const unitValue = parseFloat(option.data('unit-value')) || 0;

        $('#memberName').text(member);
        $('#accountNumber').text(accountNumber);
        $('#currentUnits').text(units.toLocaleString());

        $('#currentBalance').text(
            'KES ' + balance.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        $('#unit_value').val(unitValue.toFixed(2));

        calculateTransaction();
    }

    function updateDirection() {
        const type = $('#transaction_type').val();

        if (creditTypes.includes(type)) {
            $('#direction').val('credit');
            $('#directionWrapper').hide();
        } else if (debitTypes.includes(type)) {
            $('#direction').val('debit');
            $('#directionWrapper').hide();
        } else {
            $('#direction').val('');
            $('#directionWrapper').show();
        }

        calculateTransaction();
    }

    function calculateTransaction() {
        const option = selectedAccount();

        const currentUnits = parseInt(option.data('running-units')) || 0;
        const currentBalance = parseFloat(option.data('running-balance')) || 0;
        const unitValue = parseFloat($('#unit_value').val()) || 0;
        const units = parseInt($('#units').val()) || 0;
        const direction = $('#direction').val();

        const amount = units * unitValue;

        $('#amount').val(amount.toFixed(2));

        if (!direction) {
            $('#newUnits').text('—');
            $('#newBalance').text('—');
            return;
        }

        const newUnits = direction === 'credit'
            ? currentUnits + units
            : currentUnits - units;

        const newBalance = direction === 'credit'
            ? currentBalance + amount
            : currentBalance - amount;

        $('#newUnits').text(
            newUnits.toLocaleString()
        );

        $('#newBalance').text(
            'KES ' + newBalance.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        $('#newUnits').toggleClass('text-danger', newUnits < 0);
        $('#newBalance').toggleClass('text-danger', newBalance < 0);
    }

    $('#share_account_id').on('change', updateAccountSnapshot);
    $('#transaction_type').on('change', updateDirection);
    $('#direction, #units').on('change input', calculateTransaction);

    $('#shareTransactionForm').on('submit', function (e) {
        const option = selectedAccount();
        const status = option.data('status');
        const units = parseInt($('#units').val()) || 0;
        const direction = $('#direction').val();
        const currentUnits = parseInt(option.data('running-units')) || 0;

        if (status !== 'active') {
            e.preventDefault();
            alert('Transactions cannot be posted to a ' + status + ' share account.');
            return false;
        }

        if (units <= 0) {
            e.preventDefault();
            alert('Transaction units must be greater than zero.');
            $('#units').focus();
            return false;
        }

        if (direction === 'debit' && units > currentUnits) {
            e.preventDefault();
            alert('Insufficient share units. Available units: ' + currentUnits.toLocaleString());
            $('#units').focus();
            return false;
        }

        $('#saveBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');
    });

    updateAccountSnapshot();
    updateDirection();

});
</script>