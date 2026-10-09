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