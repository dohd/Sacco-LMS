@extends('layouts.core')

@section('title', 'Dividend Allocations')

@section('content')
    @include('dividends.allocations.header')

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>RUN</th>
                        <th>MEMBER</th>
                        <th>SHARE ACCOUNT</th>
                        <th>BALANCE</th>
                        <th>GROSS</th>
                        <th>NET</th>
                        <th>PAYMENTS</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allocations as $row)
                        <tr>
                            <td>{{ $row->run->run_number }}</td>
                            <td>{{ $row->member->full_name }}</td>
                            <td>{{ $row->shareAccount->account_number }}</td>
                            <td>{{ $row->qualifying_share_balance }}</td>
                            <td>{{ $row->gross_dividend }}</td>
                            <td>{{ $row->net_dividend }}</td>
                            <td>{{ $row->payment_option }}</td>
                            <td>{{ $row->status }}</td>
                            <td>
                                <a href="{{ route('dividend_allocations.show', $row) }}">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
