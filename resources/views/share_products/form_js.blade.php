<script>
$(document).ready(function () {

    function updateShareValues() {

        var unitValue = parseFloat($('#unit_value').val()) || 0;
        var minimumUnits = parseInt($('#minimum_units').val()) || 0;
        var maximumUnits = parseInt($('#maximum_units').val()) || 0;

        var minimumValue = unitValue * minimumUnits;

        $('#minimumShareValue').text(
            'KES ' + minimumValue.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        if (maximumUnits > 0) {

            var maximumValue = unitValue * maximumUnits;

            $('#maximumShareValue').text(
                'KES ' + maximumValue.toLocaleString('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
            );

        } else {

            $('#maximumShareValue').text('Unlimited');

        }

    }


    $('#unit_value, #minimum_units, #maximum_units')
        .on('input change', updateShareValues);


    /*
     * Validate maximum units against minimum units.
     */
    $('#shareProductForm').on('submit', function (e) {

        var minimumUnits = parseInt($('#minimum_units').val()) || 0;
        var maximumUnits = parseInt($('#maximum_units').val()) || 0;

        if (maximumUnits > 0 && maximumUnits < minimumUnits) {

            e.preventDefault();

            alert('Maximum units cannot be less than minimum units.');

            $('#maximum_units').focus();

            return false;
        }


        /*
         * Prevent duplicate submission.
         */
        $('#submitBtn')
            .prop('disabled', true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1"></span>' +
                'Saving...'
            );

    });


    updateShareValues();

});
</script>