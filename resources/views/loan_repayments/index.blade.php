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
                            <th>LOAN APPL. NUMBER#</th>
                            <th>MEMBER</th>
                            <th>AMOUNT PAID</th>
                            <th>PRINCIPAL AMOUNT</th>                            
                            <th>PAYER NAME</th>
                            <th>STATUS</th>                            
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanRepayments as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $loanRepayments->count()? '80px': '' }}">{{ $row->repayment_number }}</td>
                                    <td>{{ $row->payment_date }}</td>
                                    <td>{{ @$row->loan->loanApplication->application_number }}</td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ numberFormat($row->amount_paid) }}</td>
                                    <td>{{ numberFormat($row->principal_amount) }}</td>                                    
                                    <td>{{ $row->payer_name }}</td>
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