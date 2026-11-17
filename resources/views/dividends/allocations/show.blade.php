@extends('layouts.core')

@section('title', 'Dividend Allocation')

@section('content')
    @include('dividends.allocations.header')
    
    <h1>{{ $allocation->member->full_name }} · {{ $allocation->run->name }}</h1>
    <p>
        Balance {{ $allocation->qualifying_share_balance }},
        gross {{ $allocation->gross_dividend }},
        deductions {{ $allocation->withholding_tax + $allocation->other_deductions }},
        net {{ $allocation->net_dividend }} · {{ $allocation->status }}
    </p>

    @if ($paymentJournal)
        <p>
            Payment journal:
            <a href="{{ route('journal_entries.show', $paymentJournal) }}">
                {{ $paymentJournal->entry_number }} ({{ $paymentJournal->status }})
            </a>
        </p>
    @endif

    @if ($reversalJournal)
        <p>
            Reversal journal:
            <a href="{{ route('journal_entries.show', $reversalJournal) }}">
                {{ $reversalJournal->entry_number }}
            </a>
        </p>
    @endif

    @if ($allocation->status === 'pending')
        <a class="btn btn-primary" href="{{ route('dividend_allocations.edit', $allocation) }}">Edit</a>

        <form class="d-inline" method="POST" action="{{ route('dividend_allocations.destroy', $allocation) }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger">Delete</button>
        </form>

        <a
            class="btn btn-success"
            href="{{ route('dividend_deductions.create', ['dividend_allocation_id' => $allocation->id]) }}"
        >
            Add deduction
        </a>
    @endif

    @if ($allocation->status === 'approved' && $allocation->run->status === 'posted')
        <form class="card card-body mt-3" method="POST" action="{{ route('dividend_allocations.pay', $allocation) }}">
            @csrf
            <h2>Pay member</h2>

            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Method</label>
                    <select class="form-select" name="payment_option">
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile money</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Cash / bank / mobile money ledger</label>
                    <select class="form-select" name="payment_account_id" required>
                        @foreach (\App\Models\Accounting\ChartOfAccount::cashAndBank()->where(function ($query) {
                            $query->where('name', 'like', '%cash%')
                                ->orWhere('name', 'like', '%bank%')
                                ->orWhere('name', 'like', '%mobile money%');
                        })->orderBy('code')->get() as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->code }} — {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Paid date</label>
                    <input type="date" name="paid_date" class="form-control" required value="{{ now()->toDateString() }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Reference</label>
                    <input name="payment_reference" class="form-control">
                </div>

                <div class="col-12">
                    <button class="btn btn-success">Record payment and post journal</button>
                </div>
            </div>
        </form>
    @endif

    @if ($allocation->status === 'paid')
        <form
            class="card card-body mt-3"
            method="POST"
            action="{{ route('dividend_allocations.reverse_payment', $allocation) }}"
        >
            @csrf
            <label class="form-label">Reverse payment journal</label>
            <div class="input-group">
                <input class="form-control" name="reason" required placeholder="Reason for reversing the payment entry">
                <button class="btn btn-outline-danger">Reverse payment</button>
            </div>
            <small>This reverses the ledger entry and cancels this allocation. Confirm any external cash recovery separately.</small>
        </form>
    @endif

    <h2 class="mt-3">Deductions</h2>
    <ul>
        @foreach ($allocation->deductions as $deduction)
            <li>
                {{ $deduction->deduction_type }}: {{ $deduction->amount }}
                — {{ $deduction->description }}
            </li>
        @endforeach
    </ul>
@endsection
