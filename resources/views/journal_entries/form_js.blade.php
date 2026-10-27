<script>
$(function () {

    let lineIndex = {{ count($lines) }};

    function calculateTotals() {
        let debit = 0;
        let credit = 0;

        $('.debit').each(function () {
            debit += parseFloat($(this).val()) || 0;
        });

        $('.credit').each(function () {
            credit += parseFloat($(this).val()) || 0;
        });

        debit = debit.toFixed(2);
        credit = credit.toFixed(2);

        $('#totalDebit').text(debit);
        $('#totalCredit').text(credit);

        const difference = Math.abs(debit - credit);

        if (difference < 0.01 && debit > 0) {
            $('#balanceStatus')
                .removeClass('text-danger')
                .addClass('text-success')
                .text('Journal is balanced');
        } else {
            $('#balanceStatus')
                .removeClass('text-success')
                .addClass('text-danger')
                .text('Journal is not balanced');
        }
    }

    $('#addLine').click(function () {

        const row = `
            <tr class="journal-line">

                <td>
                    <select name="lines[${lineIndex}][account_id]"
                            class="form-select account-select"
                            required>

                        <option value="">
                            Select Account
                        </option>

                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->code }} - {{ $account->name }}
                            </option>
                        @endforeach

                    </select>
                </td>

                <td>
                    <input type="number"
                           name="lines[${lineIndex}][debit]"
                           class="form-control debit"
                           min="0"
                           step="0.01">
                </td>

                <td>
                    <input type="number"
                           name="lines[${lineIndex}][credit]"
                           class="form-control credit"
                           min="0"
                           step="0.01">
                </td>

                <td class="text-center">

                    <button type="button"
                            class="btn btn-sm btn-outline-danger remove-line">
                        <i class="fas fa-trash"></i>
                    </button>

                </td>

            </tr>
        `;

        $('#journalLines tbody').append(row);

        lineIndex++;
    });


    $(document).on('click', '.remove-line', function () {

        if ($('.journal-line').length <= 2) {
            alert('A journal must contain at least two lines.');
            return;
        }

        $(this).closest('tr').remove();

        calculateTotals();
    });


    $(document).on('input', '.debit', function () {

        if ($(this).val() > 0) {
            $(this).closest('tr').find('.credit').val('');
        }

        calculateTotals();
    });


    $(document).on('input', '.credit', function () {

        if ($(this).val() > 0) {
            $(this).closest('tr').find('.debit').val('');
        }

        calculateTotals();
    });


    $('form').on('submit', function (e) {

        let debit = 0;
        let credit = 0;

        $('.debit').each(function () {
            debit += parseFloat($(this).val()) || 0;
        });

        $('.credit').each(function () {
            credit += parseFloat($(this).val()) || 0;
        });

        if ($('.journal-line').length < 2) {
            e.preventDefault();
            alert('A journal must contain at least two lines.');
            return;
        }

        if (debit <= 0 || Math.abs(debit - credit) > 0.01) {
            e.preventDefault();
            alert('The journal must be balanced before it can be saved.');
            return;
        }

    });


    calculateTotals();

});
</script>