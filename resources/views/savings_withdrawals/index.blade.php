@extends('layouts.core')
@section('title', 'Savings Withdrawal Requests')
    
@section('content')
    @include('savings_withdrawals.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>CODE#</th>
                            <th>DATE</th>
                            <th>AMOUNT</th>
                            <th>PAYMENT METHOD</th>
                            <th>STATUS</th> 
                            <th>ACTION</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($savingsWithdrawals as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $savingsWithdrawals->count() == 1? '80px': '' }}">
                                        {{ $row->request_number }}
                                    </td>
                                    <td>{{ dateFormat($row->requested_date) }}</td>
                                    <td>{{ numberFormat($row->amount) }}</td>
                                    <td>{{ $row->payment_method }}</td>
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