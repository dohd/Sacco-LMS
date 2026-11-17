@extends('layouts.core')

@section('title', isset($allocation) ? 'Edit | Allocations' : 'Create | Allocations')

@section('content')
    @include('dividends.allocations.header')

    <form
        method="POST"
        action="{{ isset($allocation)
            ? route('dividend_allocations.update', $allocation)
            : route('dividend_allocations.store') }}"
    >
        @csrf
        @if (isset($allocation))
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Draft run</label>
                    <select class="form-select" name="dividend_run_id" required>
                        @foreach ($runs as $run)
                            <option
                                value="{{ $run->id }}"
                                @selected(old('dividend_run_id', request('dividend_run_id', $allocation->dividend_run_id ?? '')) == $run->id)
                            >
                                {{ $run->run_number }} — {{ $run->name }} ({{ $run->dividend_rate }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Share account</label>
                    <select class="form-select" name="share_account_id" required>
                        @foreach ($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('share_account_id', $allocation->share_account_id ?? '') == $account->id)
                            >
                                {{ $account->account_number }} — {{ $account->member->full_name ?? 'Member' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Qualifying balance</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control"
                        name="qualifying_share_balance"
                        required
                        value="{{ old('qualifying_share_balance', $allocation->qualifying_share_balance ?? '') }}"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Payment option</label>
                    <select class="form-select" name="payment_option">
                        <option value="">Select at payment</option>
                        @foreach (['cash', 'bank_transfer', 'mobile_money'] as $option)
                            <option
                                value="{{ $option }}"
                                @selected(old('payment_option', $allocation->payment_option ?? '') === $option)
                            >
                                {{ ucfirst(str_replace('_', ' ', $option)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Save pending allocation</button>
                    <a class="btn btn-secondary" href="{{ route('dividend_allocations.index') }}">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
