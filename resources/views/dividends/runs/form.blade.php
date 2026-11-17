@extends('layouts.core')

@section('title', $run->exists ? 'Edit | Dividend Runs' : 'Create | Dividend Runs')

@section('content')
    @include('dividends.runs.header')

    <form
        method="POST"
        action="{{ $run->exists
            ? route('dividend_runs.update', $run)
            : route('dividend_runs.store') }}"
    >
        @csrf
        @if ($run->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input
                        class="form-control"
                        name="name"
                        required
                        value="{{ old('name', $run->name) }}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Period start</label>
                    <input
                        type="date"
                        class="form-control"
                        name="period_start"
                        required
                        value="{{ old('period_start', $run->period_start) }}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Period end</label>
                    <input
                        type="date"
                        class="form-control"
                        name="period_end"
                        required
                        value="{{ old('period_end', $run->period_end) }}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Dividend rate (%)</label>
                    <input
                        type="number"
                        step="0.0001"
                        min="0"
                        class="form-control"
                        name="dividend_rate"
                        required
                        value="{{ old('dividend_rate', $run->dividend_rate) }}"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Declaration debit account (retained earnings)</label>
                    <select class="form-select" name="declaration_account_id" required>
                        <option value="">Select account</option>
                        @foreach ($accounts->filter(function ($account) {
                            return $account->account_type === 'equity'
                                && stripos($account->name, 'retained earnings') !== false;
                        }) as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('declaration_account_id', $run->declaration_account_id) == $account->id)
                            >
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Dividend payable account</label>
                    <select class="form-select" name="payable_account_id" required>
                        <option value="">Select account</option>
                        @foreach ($accounts->filter(function ($account) {
                            return $account->account_type === 'liability'
                                && stripos($account->name, 'dividend') !== false;
                        }) as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('payable_account_id', $run->payable_account_id) == $account->id)
                            >
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Withholding payable account</label>
                    <select class="form-select" name="withholding_account_id">
                        <option value="">Select account if withholding applies</option>
                        @foreach ($accounts->filter(function ($account) {
                            return $account->account_type === 'liability'
                                && stripos($account->name, 'withholding') !== false;
                        }) as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('withholding_account_id', $run->withholding_account_id) == $account->id)
                            >
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Calculation method</label>
                    <select class="form-select" name="calculation_method">
                        @foreach (['weighted_average', 'closing_balance', 'average_balance', 'minimum_balance'] as $method)
                            <option
                                value="{{ $method }}"
                                @selected(old('calculation_method', $run->calculation_method) === $method)
                            >
                                {{ ucfirst(str_replace('_', ' ', $method)) }}
                            </option>
                        @endforeach
                    </select>
                    <small>
                        Weighted average uses daily balances; average balance uses the mean of transaction
                        balances.
                    </small>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Declaration date</label>
                    <input
                        type="date"
                        class="form-control"
                        name="declaration_date"
                        value="{{ old('declaration_date', $run->declaration_date) }}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Payment date</label>
                    <input
                        type="date"
                        class="form-control"
                        name="payment_date"
                        value="{{ old('payment_date', $run->payment_date) }}"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks">{{ old('remarks', $run->remarks) }}</textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Save draft</button>
                    <a class="btn btn-secondary" href="{{ route('dividend_runs.index') }}">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
