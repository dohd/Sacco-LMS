@extends('layouts.core')
@section('title', 'Loan Products')
    
@section('content')
    @include('loan_products.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>CODE#</th>
                            <th>NAME</th>
                            <th>INTEREST RATE</th>
                            <th>MAX AMOUNT</th>
                            <th>INTEREST FREQUENCY</th>
                            <th>STATUS</th> 
                            <th>ACTION</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanProducts as $row)
                                <tr>
                                    <td style="height: {{ $loanProducts->count() == 1? '80px': '' }}">{{ $row->code }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ +$row->interest_rate }}%</td>
                                    <td>{{ numberFormat($row->maximum_amount) }}</td>
                                    <td>{{ $row->interest_frequency }}</td>
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