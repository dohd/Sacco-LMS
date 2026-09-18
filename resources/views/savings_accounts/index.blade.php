@extends('layouts.core')
@section('title', 'Savings Accounts')
    
@section('content')
    @include('savings_accounts.partial.header')
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
                            <th>LEDGER BALANCE</th>
                            <th>HELD BALANCE</th>
                            <th>AVAILABLE BALANCE</th> 
                            <th>OPENED DATE</th>
                            <th>STATUS</th>  
                            <th>ACTION</th>                          
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($savingsAccounts as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $savingsAccounts->count() == 1? '80px': '' }}">
                                        {{ $row->account_number }}
                                    </td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ @$row->savingsProduct->name }}</td>
                                    <td>{{ numberFormat($row->ledger_balance) }}</td>
                                    <td>{{ numberFormat($row->held_balance) }}</td>
                                    <td>{{ numberFormat($row->available_balance) }}</td>
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

@section('script')
<script>
    
</script>
@stop