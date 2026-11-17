@extends('layouts.core')

@section('title', 'Dividend Runs')

@section('content')
    @include('dividends.runs.header')

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-borderless datatable">
                <thead>
                    <tr>
                        <th>RUN</th>
                        <th>PERIOD</th>
                        <th>RATE</th>
                        <th>METHOD</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dividendRuns as $run)
                        <tr>
                            <td>
                                <a href="{{ route('dividend_runs.show', $run) }}">
                                    {{ $run->run_number }} · {{ $run->name }}
                                </a>
                            </td>
                            <td>{{ $run->period_start }} – {{ $run->period_end }}</td>
                            <td>{{ $run->dividend_rate }}%</td>
                            <td>{{ str_replace('_', ' ', $run->calculation_method) }}</td>
                            <td>{{ $run->status }}</td>
                            <td>
                                @if ($run->status === 'draft')
                                    <a href="{{ route('dividend_runs.edit', $run) }}">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
