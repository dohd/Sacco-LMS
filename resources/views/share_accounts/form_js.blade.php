<script>
$(document).ready(function () {

    function updateProductDetails() {
        const option = $('#share_product_id option:selected');

        const unitValue = parseFloat(option.data('unit-value')) || 0;
        const minimumUnits = option.data('minimum-units');
        const maximumUnits = option.data('maximum-units');

        $('#product_unit_value').text(
            unitValue.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        $('#preview_unit_value').val(
            unitValue.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        $('#minimum_units').text(
            minimumUnits !== undefined ? Number(minimumUnits).toLocaleString() : '—'
        );

        $('#maximum_units').text(
            maximumUnits !== undefined && maximumUnits !== ''
                ? Number(maximumUnits).toLocaleString()
                : 'No Limit'
        );

        calculateShareValue();
    }

    function calculateShareValue() {
        const units = parseInt($('#preview_units').val()) || 0;
        const unitValue = parseFloat(
            $('#share_product_id option:selected').data('unit-value')
        ) || 0;

        const value = units * unitValue;

        $('#preview_share_value').val(
            value.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );
    }

    $('#share_product_id').on('change', function () {
        updateProductDetails();
    });

    $('#preview_units').on('input', function () {
        calculateShareValue();
    });

    $('#shareAccountForm').on('submit', function () {

        const option = $('#share_product_id option:selected');

        const units = parseInt($('#preview_units').val()) || 0;
        const minimumUnits = parseInt(option.data('minimum-units')) || 1;
        const maximumUnits = option.data('maximum-units');

        if (units > 0 && units < minimumUnits) {
            alert('The number of units is below the minimum allowed for this share product.');
            return false;
        }

        if (maximumUnits !== undefined &&
            maximumUnits !== '' &&
            units > parseInt(maximumUnits)) {

            alert('The number of units exceeds the maximum allowed for this share product.');
            return false;
        }

        $('#saveBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

    });

    updateProductDetails();

});
</script>