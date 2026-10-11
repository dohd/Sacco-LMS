<script>
$(function () {

    /*
     * Show / hide password.
     */
    $('#togglePassword').on('click', function () {

        const input = $('#password');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {

            input.attr('type', 'text');

            icon.removeClass('fa-eye')
                .addClass('fa-eye-slash');

        } else {

            input.attr('type', 'password');

            icon.removeClass('fa-eye-slash')
                .addClass('fa-eye');

        }
    });


    /*
     * Confirm password.
     */
    $('#password_confirmation').on('input', function () {

        const password = $('#password').val();
        const confirmation = $(this).val();

        if (password && confirmation && password !== confirmation) {

            this.setCustomValidity(
                'Passwords do not match.'
            );

        } else {

            this.setCustomValidity('');
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