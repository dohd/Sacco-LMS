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