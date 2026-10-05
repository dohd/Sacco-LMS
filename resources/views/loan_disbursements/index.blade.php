@extends('layouts.core')
@section('title', 'Loan Disbursements')
    
@section('content')
    @include('loan_disbursements.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>DISBURSEMENT NUMBER#</th>
                            <th>DATE</th> 
                            <th>LOAN APP NUMBER#</th>
                            <th>MEMBER</th>
                            <th>GROSS AMOUNT</th>
                            <th>DEDUCTIONS AMOUNT</th>
                            <th>NET AMOUNT</th>
                            <th>STATUS</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanDisbursements as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $loanDisbursements->count() == 1? '80px': '' }}">{{ $row->disbursement_number }}</td>
                                    <td>{{ dateFormat($row->disbursement_date) }}</td>
                                    <td>{{ @$row->loanApplication->application_number }}</td>
                                    <td>{{ @$row->loanApplication->member->full_name }}</td>
                                    <td>{{ numberFormat($row->gross_amount) }}</td>
                                    <td>{{ numberFormat($row->deductions_amount) }}</td>
                                    <td>{{ numberFormat($row->net_amount) }}</td>
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