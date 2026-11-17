<script>
$(function () {

    const Form = {
        init() {
            $('#is_postable').on('change', Form.updateAccountOptions);
            $('#account_type').change(Form.changeAccountType);
            $('#parent_id').change(Form.changeParentId);
            $('#is_control_account').change(Form.changeIsControlAccount);

            const data = @json(@$chartOfAccount);
            Form.filterAccountDetails(false);
            if (data?.id) {

            } else {
                $('#is_postable').trigger('change');
                $('#account_type').trigger('change');
                $('#parent_id').trigger('change');
            }
        },

        updateAccountOptions() {
            const postable = $('#is_postable').is(':checked');
            const control = $('#is_control_account');

            if (postable) {
                control
                    .prop('checked', false)
                    .prop('disabled', true);
            } else {
                control
                    .prop('disabled', false);
            }
            control.trigger('change');
        },

        changeAccountType() {
            $('#code').val('');
            $('#parent_id').val('');
            Form.filterAccountDetails(true);

            // Fetch account code
            $.get("{{ route('chart_of_accounts.account_code') }}", {
                account_type: $(this).val(),
                is_control_account: $('#is_control_account:checked').val(),
            })
            .then(({code}) => {
                $('#code').val(code || '');
            })
            .fail((xhr, status, error) => console.log(error))
        },

        filterAccountDetails(clearSelection) {
            const accountType = $('#account_type').val();
            const $detail = $('#account_detail_id');
            const selectedDetail = clearSelection ? '' : $detail.val();

            $detail.find('option').each(function () {
                const $option = $(this);
                const matchesType = !$option.val() || $option.data('account-type') === accountType;
                $option.prop('hidden', !matchesType).prop('disabled', !matchesType);
            });

            if (selectedDetail && $detail.find(`option[value="${selectedDetail}"]:not(:disabled)`).length) {
                $detail.val(selectedDetail);
            } else {
                $detail.val('');
            }
        },

        changeParentId() {
            // Fetch parent-accounts
            $.get("{{ route('chart_of_accounts.parent_accounts') }}", {
                account_type: $('#account_type').val(),
            })
            .then(data => {
                if (data.length) {


                    {{-- let subtypes = data.map(v => v.display_subtype);
                    subtypes = [...new Set(subtypes)];
                    subtypes.forEach(type => {
                        const $optgroup = $("<optgroup>", {
                            label: type
                        });
                        data.forEach(row => {
                            if (row.display_subtype == type) {
                                $optgroup.append(
                                    $("<option>", {
                                        value: row.id,
                                        disabled: false,
                                        html: row.display_name,
                                        'data-subtype': row.subtype,
                                    })
                                );
                            }
                        });
                        $('#accountSubtype').append($optgroup);
                    });
                    $('#accountSubtype').select2({allowClear: true}); --}}
                }
            })
            .fail((xhr, status, error) => console.log(error))            
        },

        changeIsControlAccount() {
            $('#account_type').trigger('change');            
        },
    };

    $(Form.init);
});
</script>  
