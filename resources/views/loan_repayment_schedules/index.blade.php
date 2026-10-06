@extends('layouts.core')
@section('title', 'Loan Repayment Schedules')

@section('content')
    @include('loan_repayment_schedules.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>LOAN NUMBER#</th>
                            <th>MEMBER</th> 
                            <th>INSTALLMENT NUMBER#</th>
                            <th>DUE DATE</th>
                            <th>OPN. PRINCIPAL BALANCE</th>
                            <th>PRINCIPAL DUE</th>
                            <th>PRINCIPAL PAID</th>
                            <th>OUTSTANDING BALANCE</th>                            
                            <th>STATUS</th>
                            <th>ACTIONS</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $schedules->count() == 1? '80px': '' }}">{{ @$row->loan->loan_number }}</td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ $row->installment_number }}</td>
                                    <td>{{ dateFormat($row->due_date) }}</td>
                                    <td>{{ numberFormat($row->opening_principal_balance) }}</td>
                                    <td>{{ numberFormat($row->principal_due) }}</td>
                                    <td>{{ numberFormat($row->principal_paid) }}</td>                                     
                                    <td>{{ numberFormat($row->outstanding_amount) }}</td>
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