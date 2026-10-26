<div class="row g-4">

    {{-- Basic Information --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-sitemap me-2"></i>
                    Account Information
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">
                            Account Code <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="code"
                               id="code" 
                               class="form-control"
                               maxlength="50"
                               value="{{ old('code', $chartOfAccount->code ?? '') }}"
                               readonly 
                               required>

                        @error('code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">
                            Account Name <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $chartOfAccount->name ?? '') }}"
                               required>

                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    

                    <div class="col-md-3">
                        <label class="form-label">
                            Account Type <span class="text-danger">*</span>
                        </label>

                        <select name="account_type"
                                id="account_type"
                                class="form-select"
                                required>
                            @foreach([
                                'asset' => 'Asset',
                                'liability' => 'Liability',
                                'equity' => 'Equity',
                                'income' => 'Income',
                                'expense' => 'Expense',
                            ] as $value => $label)

                                <option value="{{ $value }}"
                                    @selected(old(
                                        'account_type',
                                        $chartOfAccount->account_type ?? ''
                                    ) === $value)>
                                    {{ $label }}
                                </option>

                            @endforeach
                        </select>

                        @error('account_type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">
                            Normal Balance <span class="text-danger">*</span>
                        </label>

                        <select name="normal_balance"
                                id="normal_balance"
                                class="form-select"
                                required>
                            <option value="debit"
                                @selected(old(
                                    'normal_balance',
                                    $chartOfAccount->normal_balance ?? ''
                                ) === 'debit')>
                                Debit
                            </option>

                            <option value="credit"
                                @selected(old(
                                    'normal_balance',
                                    $chartOfAccount->normal_balance ?? ''
                                ) === 'credit')>
                                Credit
                            </option>
                        </select>

                        @error('normal_balance')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Parent Account
                        </label>

                        <select name="parent_id"
                                id="parent_id"
                                class="form-select">
                            <option value="">-- Root Account --</option>

                            @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}"
                                    @selected(old(
                                        'parent_id',
                                        $chartOfAccount->parent_id ?? ''
                                    ) == $parent->id)>
                                    {{ $parent->code }} - {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('parent_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            Select the account this account should appear under.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Account Behaviour --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-sliders-h me-2"></i>
                    Account Behaviour
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden"
                                   name="is_control_account"
                                   value="0">

                            <input type="checkbox"
                                   name="is_control_account"
                                   value="1"
                                   id="is_control_account"
                                   class="form-check-input"
                                   @checked(old(
                                       'is_control_account',
                                       $chartOfAccount->is_control_account ?? false
                                   ))>

                            <label class="form-check-label"
                                   for="is_control_account">
                                Control Account
                            </label>
                        </div>

                        <div class="form-text">
                            Connects the GL to a detailed subledger.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden"
                                   name="is_postable"
                                   value="0">

                            <input type="checkbox"
                                   name="is_postable"
                                   value="1"
                                   id="is_postable"
                                   class="form-check-input"
                                   @checked(old(
                                       'is_postable',
                                       $chartOfAccount->is_postable ?? true
                                   ))>

                            <label class="form-check-label"
                                   for="is_postable">
                                Postable Account
                            </label>
                        </div>

                        <div class="form-text">
                            Allows journal entries to be posted directly.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="hidden"
                                   name="is_active"
                                   value="0">

                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   id="is_active"
                                   class="form-check-input"
                                   @checked(old(
                                       'is_active',
                                       $chartOfAccount->is_active ?? true
                                   ))>

                            <label class="form-check-label"
                                   for="is_active">
                                Active
                            </label>
                        </div>

                        <div class="form-text">
                            Inactive accounts cannot be used for new postings.
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Description --}}
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-align-left me-2"></i>
                    Description
                </h5>
            </div>
            <div class="card-body">
                <textarea
                    name="description"
                    rows="4"
                    class="form-control"
                    placeholder="Optional description..."
                >{{ old('description', $chartOfAccount->description ?? '') }}</textarea>
                @error('description')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="col-12">
        <div class="d-flex justify-content-end gap-2">
            <a
                href="{{ route('chart_of_accounts.index') }}"
                class="btn btn-light"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="fas fa-save me-1"></i>
                {{ $isEdit ? 'Update Account' : 'Create Account' }}
            </button>
        </div>
    </div>
</div>