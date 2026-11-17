@extends('layouts.core')

@section('title', 'Dividend Run')

@section('content')
    @include('dividends.runs.header')

    <h1>{{ $run->name }}</h1>
    <p>
        {{ $run->run_number }} · {{ $run->period_start }} – {{ $run->period_end }}
        · {{ $run->dividend_rate }}% · {{ $run->status }}
    </p>
    <p>{{ $run->remarks }}</p>

    @if ($run->status === 'draft')
        @if (!$run->allocations->count())
            <a class="btn btn-primary" href="{{ route('dividend_runs.edit', $run) }}">Edit draft</a>
        @endif

        <form class="d-inline" method="POST" action="{{ route('dividend_runs.calculate', $run) }}">
            @csrf
            <button
                class="btn btn-success"
                onclick="return confirm('Calculate balances from confirmed share transactions and replace current allocations?')"
            >
                Calculate allocations
            </button>
        </form>

        <form class="d-inline" method="POST" action="{{ route('dividend_runs.destroy', $run) }}">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" onclick="return confirm('Delete this empty draft?')">Delete</button>
        </form>
    @elseif ($run->status === 'calculated')
        <form class="d-inline" method="POST" action="{{ route('dividend_runs.calculate', $run) }}">
            @csrf
            <button
                class="btn btn-outline-primary"
                onclick="return confirm('Recalculate and replace current allocations?')"
            >
                Recalculate
            </button>
        </form>

        <form class="d-inline" method="POST" action="{{ route('dividend_runs.approve', $run) }}">
            @csrf
            <button class="btn btn-success">Approve run</button>
        </form>
    @elseif ($run->status === 'approved')
        <form class="d-inline" method="POST" action="{{ route('dividend_runs.post', $run) }}">
            @csrf
            <button class="btn btn-success">Post declaration to ledger</button>
        </form>
    @endif

    @if ($journalEntry)
        <p class="mt-2">
            Journal:
            <a href="{{ route('journal_entries.show', $journalEntry) }}">{{ $journalEntry->entry_number }}</a>
        </p>
    @endif

    @if (in_array($run->status, ['draft', 'calculated', 'approved', 'posted'], true))
        <form class="card card-body mt-3" method="POST" action="{{ route('dividend_runs.cancel', $run) }}">
            @csrf
            <label class="form-label">Cancel / reverse run reason</label>
            <div class="input-group">
                <input class="form-control" name="reason" required placeholder="Reason for cancellation or reversal">
                <button class="btn btn-outline-danger">Cancel run</button>
            </div>
            <small>Posted declarations are reversed in the open accounting period. Reverse paid allocations first.</small>
        </form>
    @endif

    <p>
        Qualifying share balances: {{ number_format($run->total_qualifying_shares, 2) }}
        | Gross: {{ number_format($run->gross_dividend_amount, 2) }}
        | Withholding: {{ number_format($run->total_withholding_tax, 2) }}
        | Net: {{ number_format($run->net_dividend_amount, 2) }}
    </p>

    <div class="card mt-3">
        <div class="card-body">
            <h2>Allocations</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Balance</th>
                        <th>Gross</th>
                        <th>Tax</th>
                        <th>Other deductions</th>
                        <th>Net</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($run->allocations as $allocation)
                        <tr>
                            <td>{{ $allocation->member->full_name ?? '—' }}</td>
                            <td>{{ $allocation->qualifying_share_balance }}</td>
                            <td>{{ $allocation->gross_dividend }}</td>
                            <td>{{ $allocation->withholding_tax }}</td>
                            <td>{{ $allocation->other_deductions }}</td>
                            <td>{{ $allocation->net_dividend }}</td>
                            <td>{{ $allocation->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
