<script>
$(function () {

    /*
     * Validate that the end date is not before
     * the start date.
     */
    $('#start_date, #end_date').on('change', function () {

        const start = $('#start_date').val();
        const end = $('#end_date').val();

        if (start && end && end < start) {
            $('#end_date')[0].setCustomValidity(
                'End date cannot be before start date.'
            );
        } else {
            $('#end_date')[0].setCustomValidity('');
        }
    });


    /*
     * Prevent double submission.
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