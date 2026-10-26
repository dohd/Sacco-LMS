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
                            <th>MEMBER</th>
                            <th>APPR. AMOUNT</th>
                            <th>AMOUNT DISBURSED</th>
                            <th>REPAYMENT PERIOD</th>
                            <th>MONTHLY INSTALLMENT</th>
                            <th>LOAN BALANCE</th>
                            <th>STATUS</th>                            
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loans as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $loans->count() == 1? '80px': '' }}">{{ $row->loan_number }}</td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ numberFormat($row->approved_amount) }}</td>
                                    <td>{{ numberFormat($row->amount_disbursed) }}</td>
                                    <td>{{ $row->repayment_period_months }}</td>
                                    <td>{{ numberFormat($row->monthly_installment) }}</td>
                                    <td>{{ numberFormat($row->total_outstanding_balance) }}</td>                                    
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