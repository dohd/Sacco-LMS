@extends('layouts.core')
@section('title', 'Chart Of Accounts')

@section('content')
    @include('chart_of_accounts.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>CODE#</th>
                            <th>NAME</th>
                            <th>ACCOUNT TYPE</th>
                            <th>IS CONTROL ACCOUNT</th>
                            <th>PARENT NAME</th>
                            <th>STATUS</th>                            
                            <th>ACTION</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $row)
                                <tr>
                                    <td style="height: {{ $accounts->count() == 1? '80px': '' }}">{{ $row->code }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ ucfirst($row->account_type) }}</td>
                                    <td>{{ $row->is_control_account? 'Yes' : 'No' }}</td>                                    
                                    <td>{{ @$row->parent->name }}</td>
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