<script>
$(function () {
    $('#member_id').change(function () {
        // Optional:
        // Load member details,
        // existing savings accounts,
        // eligibility,
        // etc.
    });
    $('#savings_product_id').change(function () {
        const option = this.options[this.selectedIndex];
        const fixedDeposit = option && option.dataset.productType === 'fixed_deposit';
        const fields = $('#fixedDepositOpeningFields');
        fields.toggleClass('d-none', !fixedDeposit);
        $('#initial_deposit_amount, #fixed_deposit_term_months, #fixed_deposit_deposit_account_id').prop('required', fixedDeposit);
        if (fixedDeposit) {
            $('#fixed_deposit_term_months').attr('min', option.dataset.minimumTerm || 1);
            if (option.dataset.maximumTerm) $('#fixed_deposit_term_months').attr('max', option.dataset.maximumTerm);
            else $('#fixed_deposit_term_months').removeAttr('max');
            $('#initial_deposit_amount').attr('min', option.dataset.minimumBalance || 0.01);
            if (option.dataset.maximumBalance) $('#initial_deposit_amount').attr('max', option.dataset.maximumBalance);
            else $('#initial_deposit_amount').removeAttr('max');
        }
    });
    $('#savings_product_id').trigger('change');
});
</script>
