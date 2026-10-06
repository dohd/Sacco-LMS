<script>
$(function () {

    function number(id) {
        return parseFloat($(id).val()) || 0;
    }

    function calculateSchedule() {

        const opening = number('#opening_principal_balance');
        const principal = number('#principal_due');
        const interest = number('#interest_due');
        const fees = number('#fees_due');
        const penalty = number('#penalty_due');

        const total = principal + interest + fees + penalty;

        let closing = opening - principal;

        if (closing < 0) {
            closing = 0;
        }

        $('#total_due').val(total.toFixed(2));
        $('#closing_principal_balance').val(closing.toFixed(2));
    }

    $('#opening_principal_balance, #principal_due, #interest_due, #fees_due, #penalty_due')
        .on('input change', calculateSchedule);

    calculateSchedule();

    $('#scheduleForm').on('submit', function () {

        $('#saveBtn')
            .prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin me-1"></i> Saving...'
            );
    });

});
</script>