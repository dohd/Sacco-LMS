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
                            <th>GROSS AMOUNT</th>
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