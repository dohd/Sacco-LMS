@extends('layouts.core')

@section('title', 'Loan Repayments')
    
@section('content')
    @include('loan_repayments.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>REPAYMENT NUMBER#</th>
                            <th>PAYMENT DATE</th> 
                            <th>LOAN APP NUMBER#</th>
                            <th>MEMBER</th>
                            <th>AMOUNT PAID</th>
                            <th>PRINCIPAL</th>
                            <th>INTEREST</th>
                            <th>PENALTY</th>
                            <th>FEE</th>
                            <th>PAYER NAME</th>
                            <th>STATUS</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ([] as $i => $user)
                                <tr>
                                    <th scope="row" style="height: {{ count($users) == 1? '80px': '' }}">{{ $i+1 }}</th>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->phone }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{!! $user->is_active_status_budge !!}</td>
                                    <td>{!! $user->action_buttons !!}</td>
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