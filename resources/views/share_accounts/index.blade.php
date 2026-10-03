@extends('layouts.core')
@section('title', 'Share Accounts')
    
@section('content')
    @include('share_accounts.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>ACCOUNT NO#</th>
                            <th>MEMBER</th>
                            <th>SAVINGS PRODUCT</th>
                            <th>TOTAL UNITS</th>
                            <th>SHARE BALANCE</th>
                            <th>AVAILABLE BALANCE</th> 
                            <th>OPENED DATE</th>
                            <th>STATUS</th>  
                            <th>ACTION</th>                          
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($shareAccounts as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $shareAccounts->count() == 1? '80px': '' }}">
                                        {{ $row->account_number }}
                                    </td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ @$row->shareProduct->name }}</td>
                                    <td>{{ numberFormat($row->total_units) }}</td>
                                    <td>{{ numberFormat($row->share_balance) }}</td>
                                    <td>{{ numberFormat($row->available_amount) }}</td>
                                    <td>{{ dateFormat($row->opened_date) }}</td>
                                    <td>{{ ucfirst($row->status) }}</td>
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
