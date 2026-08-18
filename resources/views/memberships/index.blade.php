@extends('layouts.core')

@section('title', 'Membeship Management')
    
@section('content')
    @include('memberships.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>MEMBERSHIP#</th>
                            <th>MEMBER</th>
                            <th>CONTACT</th>
                            <th>KYC STATUS</th>
                            <th>ACCOUNT STATUS</th>
                            <th>LOAN LIMIT</th>
                            <th>CREDIT SCORE</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberships as $app)
                                <tr>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->member_name }}</td>
                                    <td>{{ $app->phone }} @if ($app->email) <br>{{ $app->email }} @endif</td>
                                    <td>{{ $app->kyc_status }}</td>
                                    <td>{{ $app->status }}</td>
                                    <td>{{ '0.0' }}</td>
                                    <td>{{ '0' }}</td>
                                    <td>{!! $app->action_buttons !!}</td>
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