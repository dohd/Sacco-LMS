<script>
    $(document).ready(function () {
        const maxFileSize = 5 * 1024 * 1024;

        function toggleSection(toggleSelector, sectionSelector) {
            if ($(toggleSelector).is(':checked')) {
                $(sectionSelector).stop(true, true).slideDown();
            } else {
                $(sectionSelector).stop(true, true).slideUp();
            }
        }

        toggleSection('#isEmployed', '#employmentSection');
        toggleSection('#ownsBusiness', '#businessSection');

        $('#isEmployed').on('change', function () {
            toggleSection('#isEmployed', '#employmentSection');

            if (!$(this).is(':checked')) {
                $('#employmentSection')
                    .find('input, select, textarea')
                    .val('');
            }
        });

        $('#ownsBusiness').on('change', function () {
            toggleSection('#ownsBusiness', '#businessSection');

            if (!$(this).is(':checked')) {
                $('#businessSection')
                    .find('input, select, textarea')
                    .val('');
            }
        });

        $('.document-input').on('change', function () {
            const input = this;
            const file = input.files[0];
            const previewSelector = $(this).data('preview');

            $(this).removeClass('is-invalid');
            $(this).next('.client-file-error').remove();

            if (!file) {
                if (previewSelector) {
                    $(previewSelector).hide().attr('src', '');
                }

                return;
            }

            if (file.size > maxFileSize) {
                $(this).val('').addClass('is-invalid');

                $('<div class="invalid-feedback client-file-error d-block">' +
                    'The selected file must not exceed 5 MB.' +
                    '</div>').insertAfter(this);

                if (previewSelector) {
                    $(previewSelector).hide().attr('src', '');
                }

                return;
            }

            if (previewSelector && file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    $(previewSelector)
                        .attr('src', event.target.result)
                        .fadeIn();
                };

                reader.readAsDataURL(file);
            } else if (previewSelector) {
                $(previewSelector).hide().attr('src', '');
            }
        });

        $('#agreed_to_terms').on('change', function() {
            if ($(this).prop('checked')) {
                $('#submission_action').val('submit');
            } else {
                $('#submission_action').val('draft');
            }
        });

        $('#applicationForm').on('submit', function (event) {
            const form = this;

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                const firstInvalidField = $(form).find(':invalid').first();

                if (firstInvalidField.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 120
                    }, 400);

                    firstInvalidField.trigger('focus');
                }
            } else {
                $('#submitButton')
                    .prop('disabled', true)
                    .html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>' +
                        'Submitting...'
                    );
            }

            $(form).addClass('was-validated');
        });

        $('#applicationForm').on('reset', function () {
            setTimeout(function () {
                $('#employmentSection, #businessSection').hide();

                $('.document-preview')
                    .hide()
                    .attr('src', '');

                $('.client-file-error').remove();
                $('.is-invalid').removeClass('is-invalid');
                $('#applicationForm').removeClass('was-validated');
            }, 0);
        });
    });
</script>