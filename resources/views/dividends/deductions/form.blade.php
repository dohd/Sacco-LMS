@extends('layouts.core')

@section('title', isset($deduction) ? 'Edit | Deductions' : 'Record | Deductions')

@section('content')
    @include('dividends.deductions.header')

    <form
        method="POST"
        action="{{ isset($deduction)
            ? route('dividend_deductions.update', $deduction)
            : route('dividend_deductions.store') }}"
    >
        @csrf
        @if (isset($deduction))
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pending allocation</label>
                    <select class="form-select" name="dividend_allocation_id" required>
                        @foreach ($allocations as $allocation)
                            <option
                                value="{{ $allocation->id }}"
                                @selected(old('dividend_allocation_id', request('dividend_allocation_id', $deduction->dividend_allocation_id ?? '')) == $allocation->id)
                            >
                                {{ $allocation->member->full_name }} — net {{ $allocation->net_dividend }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="deduction_type">
                        @foreach (['withholding_tax', 'fee', 'other'] as $type)
                            <option
                                value="{{ $type }}"
                                @selected(old('deduction_type', $deduction->deduction_type ?? '') === $type)
                            >
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control"
                        name="amount"
                        required
                        value="{{ old('amount', $deduction->amount ?? '') }}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Destination account</label>
                    <select class="form-select" name="offset_account_id">
                        <option value="">Use run withholding account for tax</option>
                        @foreach ($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('offset_account_id', $deduction->offset_account_id ?? '') == $account->id)
                            >
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description">{{ old('description', $deduction->description ?? '') }}</textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Save deduction</button>
                </div>
            </div>
        </div>
    </form>
@endsection
