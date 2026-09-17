@extends('layouts.core')
@section('title', 'Savings Products')

@section('content')
    @include('savings_products.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>CODE#</th>
                            <th>NAME</th>
                            <th>PRODUCT TYPE</th>
                            <th>MIN MONTHLY CONTRIBUTION</th>
                            <th>ALLOWS WITHDRAWALS</th>
                            <th>WITHDRAWAL NOTICE DAYS</th>
                            <th>STATUS</th> 
                            <th>ACTION</th>                          
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($savingsProducts as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $savingsProducts->count() == 1? '80px': '' }}">
                                        {{ $row->code }}
                                    </td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->product_type }}</td>
                                    <td>{{ $row->minimum_monthly_contribution }}</td>
                                    <td>{{ $row->allows_withdrawals }}</td>
                                    <td>{{ $row->withdrawal_notice_days }}</td>
                                    <td>{{ $row->is_active? 'Active' : 'Inactive' }}</td>
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