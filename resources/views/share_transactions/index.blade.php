@extends('layouts.core')
@section('title', 'Share Transactions')
    
@section('content')
    @include('share_transactions.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>TRANS NO#</th>
                            <th>TRANS TYPE</th>
                            <th>DATE</th>
                            <th>AMOUNT</th>
                            <th>PAYMENT METHOD</th>
                            <th>REFERENCE</th> 
                            <th>STATUS</th>   
                            <th>ACTION</th>                         
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($shareTransactions as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $shareTransactions->count() == 1? '80px': '' }}">
                                        {{ $row->transaction_number }}
                                    </td>
                                    <td>{{ $row->transaction_type }}</td>
                                    <td>{{ dateFormat($row->transaction_date)  }}</td>
                                    <td>{{ $row->amount }}</td>
                                    <td>{{ $row->payment_method }}</td>
                                    <td>{{ $row->external_reference }}</td>
                                    <td>{{ $row->status }}</td>                                    
                                    <td>{!! $row->action_buttons !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
