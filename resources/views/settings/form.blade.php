<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>SACCO Profile</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="SACCO_NAME">SACCO name</label>
                                <input class="form-control" id="SACCO_NAME" name="SACCO_NAME" required maxlength="255" value="{{ old('SACCO_NAME', $settings['SACCO_NAME'] ?? config('app.name')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="SACCO_REGISTRATION_NUMBER">Registration number</label>
                                <input class="form-control" id="SACCO_REGISTRATION_NUMBER" name="SACCO_REGISTRATION_NUMBER" maxlength="100" value="{{ old('SACCO_REGISTRATION_NUMBER', $settings['SACCO_REGISTRATION_NUMBER'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="SACCO_LOGO">SACCO logo</label>
                                <input type="file" class="form-control" id="SACCO_LOGO" name="SACCO_LOGO" accept=".png,.jpg,.jpeg,.webp">
                                <small class="text-muted">PNG, JPG, or WebP, up to 2 MB.</small>
                            </div>
                            @if (!empty($settings['SACCO_LOGO']))
                                <div class="col-md-6 d-flex align-items-center">
                                    <img src="{{ asset('storage/' . $settings['SACCO_LOGO']) }}" alt="Current SACCO logo" style="max-height: 64px; max-width: 220px;">
                                </div>
                            @endif
                            <div class="col-md-4">
                                <label class="form-label" for="SACCO_PHONE">Phone</label>
                                <input class="form-control" id="SACCO_PHONE" name="SACCO_PHONE" value="{{ old('SACCO_PHONE', $settings['SACCO_PHONE'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="SACCO_EMAIL">Email</label>
                                <input type="email" class="form-control" id="SACCO_EMAIL" name="SACCO_EMAIL" value="{{ old('SACCO_EMAIL', $settings['SACCO_EMAIL'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="SACCO_WEBSITE">Website</label>
                                <input type="url" class="form-control" id="SACCO_WEBSITE" name="SACCO_WEBSITE" value="{{ old('SACCO_WEBSITE', $settings['SACCO_WEBSITE'] ?? '') }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="SACCO_ADDRESS">Postal / physical address</label>
                                <input class="form-control" id="SACCO_ADDRESS" name="SACCO_ADDRESS" maxlength="1000" value="{{ old('SACCO_ADDRESS', $settings['SACCO_ADDRESS'] ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="SACCO_CURRENCY">Currency code</label>
                                <input class="form-control text-uppercase" id="SACCO_CURRENCY" name="SACCO_CURRENCY" maxlength="3" pattern="[A-Z]{3}" required value="{{ old('SACCO_CURRENCY', $settings['SACCO_CURRENCY'] ?? 'KES') }}">
                                <small class="text-muted">Three-letter code, for example KES.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Membership and Financial Year</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="MEMBERSHIP_NUMBER_PREFIX">Membership number prefix</label>
                                <input class="form-control text-uppercase" id="MEMBERSHIP_NUMBER_PREFIX" name="MEMBERSHIP_NUMBER_PREFIX" maxlength="12" pattern="[A-Za-z0-9]+(-[A-Za-z0-9]+)*" title="Use letters and numbers, with optional single hyphens between sections." required value="{{ old('MEMBERSHIP_NUMBER_PREFIX', $settings['MEMBERSHIP_NUMBER_PREFIX'] ?? 'MEM') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="MEMBERSHIP_NUMBER_SEQUENCE_LENGTH">Sequence digits</label>
                                <input type="number" class="form-control" id="MEMBERSHIP_NUMBER_SEQUENCE_LENGTH" name="MEMBERSHIP_NUMBER_SEQUENCE_LENGTH" min="3" max="10" required value="{{ old('MEMBERSHIP_NUMBER_SEQUENCE_LENGTH', $settings['MEMBERSHIP_NUMBER_SEQUENCE_LENGTH'] ?? 6) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="PASSWORD_MIN_LENGTH">Minimum password length</label>
                                <input type="number" class="form-control" id="PASSWORD_MIN_LENGTH" name="PASSWORD_MIN_LENGTH" min="8" max="64" required value="{{ old('PASSWORD_MIN_LENGTH', $settings['PASSWORD_MIN_LENGTH'] ?? 10) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="FINANCIAL_YEAR_START_MONTH">Financial year start month</label>
                                <select class="form-select" id="FINANCIAL_YEAR_START_MONTH" name="FINANCIAL_YEAR_START_MONTH" required>
                                    @foreach (range(1, 12) as $month)
                                        <option value="{{ $month }}" @selected((int) old('FINANCIAL_YEAR_START_MONTH', $settings['FINANCIAL_YEAR_START_MONTH'] ?? 1) === $month)>{{ \Carbon\Carbon::create()->month($month)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="FINANCIAL_YEAR_START_DAY">Financial year start day</label>
                                <input type="number" class="form-control" id="FINANCIAL_YEAR_START_DAY" name="FINANCIAL_YEAR_START_DAY" min="1" max="31" required value="{{ old('FINANCIAL_YEAR_START_DAY', $settings['FINANCIAL_YEAR_START_DAY'] ?? 1) }}">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">The membership prefix and sequence length apply when a member application is approved and when an import creates a new member. New numbers use PREFIX-YEAR-SEQUENCE. Financial year settings prefill the start, end, and year on new accounting periods; existing periods are unchanged.</small>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-success text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Accounting Defaults</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach ([
                                'RETAINED_EARNINGS_ACCOUNT_ID' => ['Retained earnings', 'equity'],
                                'OPENING_BALANCE_EQUITY_ACCOUNT_ID' => ['Opening balance equity', 'equity'],
                                'DIVIDEND_PAYABLE_ACCOUNT_ID' => ['Dividend payable', 'liability'],
                                'WITHHOLDING_TAX_ACCOUNT_ID' => ['Withholding tax payable', 'liability'],
                            ] as $settingKey => $accountConfig)
                                <div class="col-md-6">
                                    <label class="form-label" for="{{ $settingKey }}">{{ $accountConfig[0] }}</label>
                                    <select class="form-select" id="{{ $settingKey }}" name="{{ $settingKey }}">
                                        <option value="">Not configured</option>
                                        @foreach ($accounts->where('account_type', $accountConfig[1]) as $account)
                                            <option value="{{ $account->id }}" @selected((string) old($settingKey, $settings[$settingKey] ?? '') === (string) $account->id)>
                                                {{ $account->code }} — {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted d-block mt-3">These defaults are offered to accounting workflows. Each posting still validates the selected ledger account.</small>
                    </div>
                </div>


                <!-- Form Submission -->
                {{-- <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-light border">Reset Changes</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-bold">Save Configurations</button>
                </div> --}}
            {{-- </form> --}}

        </div>
    </div>
</div>
