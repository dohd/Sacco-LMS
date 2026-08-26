<script>
    $(document).ready(function () {
        const maximumNominees = 5;
        const maximumFileSize = 5 * 1024 * 1024;

        function nomineeTemplate(index) {
            return `
                <div class="card nominee-card nominee-row" data-index="${index}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>
                            Nominee <span class="nominee-number">${index + 1}</span>
                        </strong>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger remove-nominee"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][full_name]"
                                    class="form-control nominee-field"
                                    data-field="full_name"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    National ID Number
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][national_id]"
                                    class="form-control nominee-field"
                                    data-field="national_id"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Postal Address
                                </label>

                                <input
                                    type="text"
                                    name="nominees[${index}][postal_address]"
                                    class="form-control nominee-field"
                                    data-field="postal_address"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="nominees[${index}][phone]"
                                    class="form-control nominee-field"
                                    data-field="phone"
                                    placeholder="+254 7XX XXX XXX"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="nominees[${index}][email]"
                                    class="form-control nominee-field"
                                    data-field="email"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Relationship
                                </label>

                                <select
                                    name="nominees[${index}][relationship]"
                                    class="form-select nominee-field"
                                    data-field="relationship"
                                    required
                                >
                                    <option value="">Select relationship</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Child">Child</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Relative">Relative</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required-label">
                                    Allocation Percentage
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        name="nominees[${index}][percentage]"
                                        class="form-control nominee-field percentage-input"
                                        data-field="percentage"
                                        min="0.01"
                                        max="100"
                                        step="0.01"
                                        required
                                    >

                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input
                                        type="hidden"
                                        name="nominees[${index}][is_minor]"
                                        value="0"
                                        class="minor-hidden"
                                    >

                                    <input
                                        type="checkbox"
                                        name="nominees[${index}][is_minor]"
                                        value="1"
                                        class="form-check-input minor-toggle"
                                        id="minor_${index}"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="minor_${index}"
                                    >
                                        Nominee is under 18 years
                                    </label>
                                </div>
                            </div>

                            <div
                                class="col-md-6 minor-date-container"
                                style="display:none;"
                            >
                                <label class="form-label required-label">
                                    Date of Birth
                                </label>

                                <input
                                    type="date"
                                    name="nominees[${index}][date_of_birth]"
                                    class="form-control nominee-field nominee-date-of-birth"
                                    data-field="date_of_birth"
                                    max="{{ now()->toDateString() }}"
                                >
                            </div>

                        </div>
                    </div>
                </div>
            `;
        }

        function reindexNominees() {
            $('.nominee-row').each(function (index) {
                const row = $(this);

                row.attr('data-index', index);
                row.find('.nominee-number').text(index + 1);

                row.find('.nominee-field').each(function () {
                    const field = $(this).data('field');

                    $(this).attr(
                        'name',
                        `nominees[${index}][${field}]`
                    );
                });

                row.find('.minor-hidden').attr(
                    'name',
                    `nominees[${index}][is_minor]`
                );

                row.find('.minor-toggle')
                    .attr('name', `nominees[${index}][is_minor]`)
                    .attr('id', `minor_${index}`);

                row.find('.minor-toggle')
                    .next('label')
                    .attr('for', `minor_${index}`);
            });

            updateNomineeControls();
        }

        function updateNomineeControls() {
            const nomineeCount = $('.nominee-row').length;

            $('.remove-nominee').toggle(nomineeCount > 1);

            $('#addNomineeButton').prop(
                'disabled',
                nomineeCount >= maximumNominees
            );
        }

        function calculatePercentageTotal() {
            let total = 0;

            $('.percentage-input').each(function () {
                const value = parseFloat($(this).val());

                if (!isNaN(value)) {
                    total += value;
                }
            });

            total = Math.round(total * 100) / 100;

            const remaining = Math.round((100 - total) * 100) / 100;

            $('#percentageTotal').text(total.toFixed(2));
            $('#percentageRemaining').text(remaining.toFixed(2));

            const alert = $('#percentageAlert');
            const message = $('#percentageMessage');

            alert.removeClass(
                'alert-warning alert-success alert-danger'
            );

            if (Math.abs(total - 100) < 0.001) {
                alert.addClass('alert-success');
                message.text('The nominee allocation is complete.');
            } else if (total > 100) {
                alert.addClass('alert-danger');
                message.text('The total allocation exceeds 100%.');
            } else {
                alert.addClass('alert-warning');
                message.text('Total nominee allocation must equal 100%.');
            }

            return total;
        }

        $('#addNomineeButton').on('click', function () {
            const nomineeCount = $('.nominee-row').length;

            if (nomineeCount >= maximumNominees) {
                return;
            }

            $('#nomineesContainer').append(
                nomineeTemplate(nomineeCount)
            );

            reindexNominees();
            calculatePercentageTotal();
        });

        $(document).on('click', '.remove-nominee', function () {
            if ($('.nominee-row').length <= 1) {
                return;
            }

            $(this).closest('.nominee-row').remove();

            reindexNominees();
            calculatePercentageTotal();
        });

        $(document).on('input', '.percentage-input', function () {
            calculatePercentageTotal();
        });

        $(document).on('change', '.minor-toggle', function () {
            const row = $(this).closest('.nominee-row');
            const dateContainer = row.find('.minor-date-container');
            const dateInput = row.find('.nominee-date-of-birth');

            if ($(this).is(':checked')) {
                dateContainer.stop(true, true).slideDown();
                dateInput.prop('required', true);
            } else {
                dateContainer.stop(true, true).slideUp();
                dateInput.prop('required', false).val('');
            }
        });

        $('.signature-input').on('change', function () {
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

            if (file.size > maximumFileSize) {
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

        $('#nominationForm').on('submit', function (event) {
            const form = this;
            const percentageTotal = calculatePercentageTotal();

            if (Math.abs(percentageTotal - 100) >= 0.001) {
                event.preventDefault();
                event.stopPropagation();

                $('#percentageAlert')
                    .removeClass('alert-warning alert-success')
                    .addClass('alert-danger');

                $('#percentageMessage').text(
                    'You cannot submit the nomination until the total allocation equals 100%.'
                );

                $('html, body').animate({
                    scrollTop: $('#nomineesContainer').offset().top - 100
                }, 400);

                return;
            }

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

        $('#nominationForm').on('reset', function () {
            setTimeout(function () {
                while ($('.nominee-row').length > 1) {
                    $('.nominee-row').last().remove();
                }

                $('.minor-date-container').hide();
                $('.nominee-date-of-birth').prop('required', false);

                $('.signature-preview')
                    .hide()
                    .attr('src', '');

                $('.client-file-error').remove();
                $('.is-invalid').removeClass('is-invalid');

                $('#nominationForm').removeClass('was-validated');

                reindexNominees();
                calculatePercentageTotal();
            }, 0);
        });

        reindexNominees();
        calculatePercentageTotal();
    });
</script>