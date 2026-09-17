<script>
$(function () {

    function setHiddenBoolean(name, checked) {
        const $hidden = $('input[type="hidden"][name="' + name + '"]');
        if ($hidden.length) {
            $hidden.val(checked ? '0' : '0');
        }
    }

    function toggleFixedDepositSection() {
        const isFixedDeposit = $('#product_type').val() === 'fixed_deposit';

        $('#fixedDepositSection').toggleClass('d-none', !isFixedDeposit);

        $('#minimum_term_months, #maximum_term_months, #allows_premature_withdrawal, #auto_rollover, #allows_partial_withdrawals')
            .prop('disabled', !isFixedDeposit);

        if (!isFixedDeposit) {
            $('#minimum_term_months').val(0);
            $('#maximum_term_months').val('');
            $('#allows_premature_withdrawal').prop('checked', false);
            $('#premature_withdrawal_penalty_percentage').val(0);
            $('#auto_rollover').prop('checked', false);
            $('#rollover_option').val('');
            $('#allows_partial_withdrawals').prop('checked', false);
        }

        togglePrematureWithdrawal();
        toggleRollover();
    }

    function togglePrematureWithdrawal() {
        const fixedDeposit = $('#product_type').val() === 'fixed_deposit';
        const allowed = fixedDeposit && $('#allows_premature_withdrawal').is(':checked');

        $('#prematurePenaltyContainer').toggleClass('d-none', !allowed);
        $('#premature_withdrawal_penalty_percentage').prop('disabled', !allowed);

        if (!allowed) {
            $('#premature_withdrawal_penalty_percentage').val(0);
        }
    }

    function toggleRollover() {
        const fixedDeposit = $('#product_type').val() === 'fixed_deposit';
        const enabled = fixedDeposit && $('#auto_rollover').is(':checked');

        $('#rolloverOptionContainer').toggleClass('d-none', !enabled);
        $('#rollover_option').prop('disabled', !enabled);

        if (!enabled) {
            $('#rollover_option').val('');
        }
    }

    function toggleWithdrawalRules() {
        const allowed = $('#allows_withdrawals').is(':checked');

        $('#withdrawalNoticeContainer').toggleClass('d-none', !allowed);
        $('#withdrawal_notice_days').prop('disabled', !allowed);

        if (!allowed) {
            $('#withdrawal_notice_days').val(0);
        }
    }

    function updateProductStatus() {
        $('#productStatus').val(
            $('#is_active').is(':checked') ? 'Active' : 'Inactive'
        );
    }

    function showValidationError(message) {
        const $alert = $('#clientValidationAlert');

        if ($alert.length) {
            $alert.find('.validation-message').text(message);
            $alert.removeClass('d-none');
        } else {
            alert(message);
        }

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function validateProduct() {

        const productType = $('#product_type').val();

        const minBalance = parseFloat($('#minimum_balance').val()) || 0;
        const maxBalance = parseFloat($('#maximum_balance').val());

        const interestRate = parseFloat($('#interest_rate').val()) || 0;
        const minContribution = parseFloat($('#minimum_monthly_contribution').val()) || 0;

        if (minBalance < 0) {
            showValidationError('Minimum balance cannot be negative.');
            return false;
        }

        if (minContribution < 0) {
            showValidationError('Minimum monthly contribution cannot be negative.');
            return false;
        }

        if (!isNaN(maxBalance) && maxBalance >= 0 && maxBalance < minBalance) {
            showValidationError('Maximum balance cannot be less than minimum balance.');
            return false;
        }

        if (interestRate < 0 || interestRate > 100) {
            showValidationError('Interest rate must be between 0% and 100%.');
            return false;
        }

        if (productType === 'fixed_deposit') {

            const minTerm = parseInt($('#minimum_term_months').val(), 10) || 0;
            const maxTermValue = $('#maximum_term_months').val();
            const maxTerm = maxTermValue === '' ? null : parseInt(maxTermValue, 10);

            if (minTerm < 1) {
                showValidationError('Fixed deposit products must have a minimum term of at least 1 month.');
                return false;
            }

            if (maxTerm !== null && maxTerm < minTerm) {
                showValidationError('Maximum term cannot be less than minimum term.');
                return false;
            }

            if ($('#allows_premature_withdrawal').is(':checked')) {
                const penalty = parseFloat($('#premature_withdrawal_penalty_percentage').val());

                if (isNaN(penalty) || penalty <= 0 || penalty > 100) {
                    showValidationError(
                        'Premature withdrawal penalty must be greater than 0% and not exceed 100%.'
                    );
                    return false;
                }
            }

            if ($('#auto_rollover').is(':checked') && !$('#rollover_option').val()) {
                showValidationError('Please select a rollover option.');
                return false;
            }
        }

        const accountIds = [
            $('#savings_control_account_id').val(),
            $('#interest_expense_account_id').val(),
            $('#fee_income_account_id').val()
        ];

        if (accountIds.some(function (value) {
            return !value;
        })) {
            showValidationError('Please select all required GL accounts.');
            return false;
        }

        {{-- if (new Set(accountIds).size !== accountIds.length) {
            showValidationError(
                'Savings control, interest expense and fee income accounts must be different.'
            );
            return false;
        } --}}

        return true;
    }

    $('#product_type').on('change', toggleFixedDepositSection);
    $('#allows_premature_withdrawal').on('change', togglePrematureWithdrawal);
    $('#auto_rollover').on('change', toggleRollover);
    $('#allows_withdrawals').on('change', toggleWithdrawalRules);
    $('#is_active').on('change', updateProductStatus);

    $('#savingsProductForm').on('submit', function (e) {

        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();

            $(this).addClass('was-validated');
            return false;
        }

        if (!validateProduct()) {
            e.preventDefault();
            return false;
        }

        $('#saveButton')
            .prop('disabled', true)
            .html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
            );
    });

    // Normalize product code
    $('#code').on('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
    });

    // Initial state
    toggleFixedDepositSection();
    togglePrematureWithdrawal();
    toggleRollover();
    toggleWithdrawalRules();
    updateProductStatus();
});
</script>