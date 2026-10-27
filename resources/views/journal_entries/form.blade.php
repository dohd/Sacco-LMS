<div class="row g-4">

    {{-- Journal Information --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-book me-2"></i>
                    Journal Entry
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Accounting Period <span class="text-danger">*</span>
                        </label>

                        <select name="accounting_period_id"
                                class="form-select"
                                required>
                            <option value="">Select Period</option>

                            @foreach($accountingPeriods as $period)
                                <option value="{{ $period->id }}"
                                    @selected(old(
                                        'accounting_period_id',
                                        $journalEntry->accounting_period_id ?? ''
                                    ) == $period->id)>
                                    {{ $period->name }}
                                    ({{ $period->start_date->format('d M Y') }}
                                    -
                                    {{ $period->end_date->format('d M Y') }})
                                </option>
                            @endforeach
                        </select>

                        @error('accounting_period_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Transaction Date <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="transaction_date"
                               class="form-control"
                               value="{{ old('transaction_date', isset($journalEntry) ? $journalEntry->transaction_date->format('Y-m-d') : date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Posting Date <span class="text-danger">*</span>
                        </label>

                        <input type="date"
                               name="posting_date"
                               class="form-control"
                               value="{{ old('posting_date', isset($journalEntry) ? $journalEntry->posting_date->format('Y-m-d') : date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Reference Number
                        </label>

                        <input type="text"
                               name="reference_number"
                               class="form-control"
                               maxlength="255"
                               value="{{ old('reference_number', $journalEntry->reference_number ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Event Type
                        </label>

                        <input type="text"
                               name="event_type"
                               class="form-control"
                               value="{{ old('event_type', $journalEntry->event_type ?? 'manual') }}"
                               readonly>

                        <div class="form-text">
                            Manual journals are identified as manual entries.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Description <span class="text-danger">*</span>
                        </label>

                        <textarea name="description"
                                  rows="3"
                                  class="form-control"
                                  required>{{ old('description', $journalEntry->description ?? '') }}</textarea>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- Journal Lines --}}
    <div class="col-12">

        <div class="card shadow-sm border-0">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Journal Lines
                </h5>

                <button type="button"
                        id="addLine"
                        class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Add Line
                </button>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered align-middle mb-0"
                           id="journalLines">

                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%;">Account</th>
                                <th style="width: 20%;">Debit</th>
                                <th style="width: 20%;">Credit</th>
                                <th style="width: 10%;"></th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($lines as $index => $line)

                                <tr class="journal-line">

                                    <td>
                                        <select name="lines[{{ $index }}][account_id]"
                                                class="form-select account-select"
                                                required>

                                            <option value="">
                                                Select Account
                                            </option>

                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    @selected(($line['chart_of_account_id'] ?? $line->chart_of_account_id ?? '') == $account->id)>
                                                    {{ $account->code }}
                                                    -
                                                    {{ $account->name }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </td>

                                    <td>
                                        <input type="number"
                                               name="lines[{{ $index }}][debit]"
                                               class="form-control debit"
                                               min="0"
                                               step="0.01"
                                               value="{{ $line['debit'] ?? $line->debit ?? '' }}">
                                    </td>

                                    <td>
                                        <input type="number"
                                               name="lines[{{ $index }}][credit]"
                                               class="form-control credit"
                                               min="0"
                                               step="0.01"
                                               value="{{ $line['credit'] ?? $line->credit ?? '' }}">
                                    </td>

                                    <td class="text-center">

                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger remove-line">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="table-light">

                            <tr>
                                <th class="text-end">
                                    Totals
                                </th>

                                <th>
                                    <span id="totalDebit">
                                        0.00
                                    </span>
                                </th>

                                <th>
                                    <span id="totalCredit">
                                        0.00
                                    </span>
                                </th>

                                <th></th>
                            </tr>

                            <tr>
                                <th colspan="4"
                                    id="balanceStatus"
                                    class="text-center">
                                </th>
                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>
        </div>

    </div>


    {{-- Actions --}}
    <div class="col-12">

        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('journal_entries.index') }}"
               class="btn btn-light">
                Cancel
            </a>

            <button type="submit"
                    class="btn btn-primary"
                    id="saveJournal">

                <i class="fas fa-save me-1"></i>

                {{ $isEdit ? 'Update Journal' : 'Save Journal' }}

            </button>

        </div>

    </div>

</div>