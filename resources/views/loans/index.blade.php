@extends('layouts.core')
@section('title', 'Loan Accounts')
    
@section('content')
    @include('loans.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>LOAN NO.#</th>
                            <th>APPL. NUMBER#</th>
                            <th>APPROVED AMOUNT</th>
                            <th>REPAYMENT PERIOD</th>
                            <th>INTEREST RATE</th>
                            <th>MONTHLY INSTALLMENT</th>
                            <th>INTEREST METHOD</th>
                            <th>AMOUNT DISBURSED</th>
                            <th>STATUS</th>                            
                            <th>LOAN BALANCE</th>
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loans as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $loans->count() == 1? '80px': '' }}">{{ $row->loan_number }}</td>
                                    <td>{{ @$row->loanApplication->application_number }}</td>
                                    <td>{{ numberFormat($row->approved_amount) }}</td>
                                    <td>{{ $row->repayment_period_months }}</td>
                                    <td>{{ +$row->interest_rate }}</td>
                                    <td>{{ +$row->monthly_installment }}</td>
                                    <td>{{ ucfirst($row->interest_method) }}</td>
                                    <td>{{ numberFormat($row->amount_disbursed) }}</td>
                                    <td>{{ ucfirst($row->status) }}</td>
                                    <td>{{ numberFormat($row->total_outstanding_balance) }}</td>                                    
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