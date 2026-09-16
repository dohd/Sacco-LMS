@extends('layouts.core')
@section('title', 'Loan Applications')
    
@section('content')
    @include('loan_applications.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>APPLICATION NO#</th>
                            <th>LOAN PRODUCT</th>
                            <th>MEMBER</th>
                            <th>AMOUNT REQUESTED</th>
                            <th>MONTHLY INSTALLMENT</th>
                            <th>REQUIRED DATE</th>
                            <th>STATUS</th>
                            <th>DECLARATION DATE</th>
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanApplications as $i => $row)
                                <tr>
                                    <td style="height: {{ $loanApplications->count() == 1? '80px': '' }}">{{ $row->application_number }}</td>
                                    <td>{{ @$row->loanProduct->name }}</td>
                                    <td>{{ @$row->member->full_name }}</td>
                                    <td>{{ numberFormat($row->amount_requested) }}</td>
                                    <td>{{ $row->monthly_installment }}</td>
                                    <td>{{ dateFormat($row->required_date) }}</td>
                                    <td>{{ ucfirst($row->status) }}</td>
                                    <td>{{ dateFormat($row->declaration_date) }}</td>
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