<script>
$(function () {
    function toggleGuarantors() {
        if ($('#requires_guarantors').is(':checked')) {

            $('#guarantorSection').slideDown(200);

        } else {

            $('#guarantorSection').slideUp(200);

        }
    }

    function toggleTopUp() {
        if ($('#allows_top_up').is(':checked')) {

            $('#topupPercentageContainer').slideDown(200);

        } else {

            $('#topupPercentageContainer').slideUp(200);

            $('input[name="minimum_repaid_percentage_for_top_up"]')
                .val('');

        }
    }

    toggleGuarantors();

    toggleTopUp();

    $('#requires_guarantors').change(function () {

        toggleGuarantors();

    });

    $('#allows_top_up').change(function () {

        toggleTopUp();

    });

    $('#loanProductForm').submit(function () {

        let minAmount = parseFloat(
            $('input[name="minimum_amount"]').val() || 0
        );

        let maxAmount = parseFloat(
            $('input[name="maximum_amount"]').val() || 0
        );

        if (maxAmount > 0 && maxAmount < minAmount) {

            alert(
                'Maximum loan amount cannot be less than the minimum loan amount.'
            );

            return false;

        }

        let minMonths = parseInt(
            $('input[name="minimum_repayment_months"]').val() || 0
        );

        let maxMonths = parseInt(
            $('input[name="maximum_repayment_months"]').val() || 0
        );

        if (maxMonths < minMonths) {

            alert(
                'Maximum repayment period cannot be less than the minimum repayment period.'
            );

            return false;

        }

        if ($('#requires_guarantors').is(':checked')) {

            let minGuarantors = parseInt(
                $('input[name="minimum_guarantors"]').val() || 0
            );

            let maxGuarantors = parseInt(
                $('input[name="maximum_guarantors"]').val() || 0
            );

            if (maxGuarantors > 0 &&
                maxGuarantors < minGuarantors) {

                alert(
                    'Maximum guarantors cannot be less than minimum guarantors.'
                );

                return false;

            }

        }

        if ($('#allows_top_up').is(':checked')) {

            let repaid = parseFloat(
                $('input[name="minimum_repaid_percentage_for_top_up"]')
                .val() || 0
            );

            if (repaid < 0 || repaid > 100) {

                alert(
                    'Minimum repaid percentage must be between 0 and 100.'
                );

                return false;

            }

        }

        let json = $('textarea[name="eligibility_rules"]').val().trim();

        if (json.length > 0) {

            try {

                JSON.parse(json);

            } catch (e) {

                alert('Eligibility Rules contains invalid JSON.');

                return false;

            }

        }

        return true;

    });
});
</script>