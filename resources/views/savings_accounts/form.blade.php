<!-- ============================= -->
<!-- ACCOUNT DETAILS -->
<!-- ============================= -->

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            Savings Account
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">
                    Opened Date
                    <span class="text-danger">*</span>
                </label>
                <input type="date"
                       name="opened_date"
                       class="form-control"
                       value="{{ old('opened_date', @$savingsAccount->opened_date ?: now()->format('Y-m-d')) }}"
                       required>
            </div> 
            
            <div class="col-md-5 mb-3">
                <label class="form-label">
                    Member
                    <span class="text-danger">*</span>
                </label>
                <select name="member_id"
                        id="member_id"
                        class="form-select"
                        required>
                    <option value="">
                        Select Member
                    </option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}"
                            {{ old('member_id', $savingsAccount->member_id ?? '') == $member->id ? 'selected' : '' }}>
                            {{ $member->membership_number }} -  {{ $member->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Savings Product
                    <span class="text-danger">*</span>
                </label>
                <select name="savings_product_id"
                        id="savings_product_id"
                        class="form-select"
                        required>
                    <option value="">
                        Select Product
                    </option>
                    @foreach($savingsProducts as $product)
                        <option value="{{ $product->id }}"
                            {{ old('savings_product_id', $savingsAccount->savings_product_id ?? '') == $product->id ? 'selected' : '' }}>
                            {{ $product->code }}
                            -
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>                        
        </div>

        @isset($savingsAccount)
        	<div class="row">
        		<div class="col-md-3 mb-3">
                    <label class="form-label">
                        Status
                    </label>
                    <select name="status" class="form-select">
                        <option value="active"
                            {{ old('status', $account->status ?? 'active') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="frozen"
                            {{ old('status', $account->status ?? '') == 'frozen' ? 'selected' : '' }}>
                            Frozen
                        </option>
                        <option value="closed"
                            {{ old('status', $account->status ?? '') == 'closed' ? 'selected' : '' }}>
                            Closed
                        </option>
                    </select>
                </div>
        	</div>
        @endisset
        
    </div>
</div>

<!-- ============================= -->
<!-- ACCOUNT BALANCES -->
<!-- ============================= -->

@isset($savingsAccount)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            Account Balances
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Ledger Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->ledger_balance,2) }}"
                           readonly>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Held Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->held_balance,2) }}"
                           readonly>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">
                    Available Balance
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        KES
                    </span>
                    <input type="text"
                           class="form-control text-end bg-light"
                           value="{{ number_format($savingsAccount->available_balance,2) }}"
                           readonly>
                </div>
            </div>
        </div>
    </div>
</div>
@endisset

<!-- ============================= -->
<!-- ACTIONS -->
<!-- ============================= -->

<div class="card shadow-sm">
    <div class="card-body d-flex justify-content-between">
        <a href="{{ route('savings_accounts.index') }}"
           class="btn btn-light">
            Cancel
        </a>
        <button type="submit"
                class="btn btn-primary">
            Save Savings Account
        </button>
    </div>
</div>
