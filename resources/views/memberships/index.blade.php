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
                            <th>APPLICATION NO#</th>
                            <th>APPLICANT NAME</th>
                            <th>CONTACT</th>
                            <th>APPLICATION DATE</th> 
                            <th>ACCOUNT STATUS</th> 
                            <th>MEMBERSHIP NO#</th>                           
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberships as $appl)
                                <tr>
                                    <td style="height: {{ $memberships->count() == 1? '80px': '' }}">{{ $appl->application_number }}</td>
                                    <td>{{ $appl->member_name }}</td>
                                    <td>{{ $appl->phone }} @if ($appl->email) <br>{{ $appl->email }} @endif</td>
                                    <td>{{ dateFormat($appl->application_date, 'd M Y') }}</td> 
                                    <td>
                                        @php
                                            $statusClasses = [
                                                'draft' => 'secondary',
                                                'pending' => 'warning',
                                                'under_review' => 'info',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'withdrawn' => 'dark',
                                                'cancelled' => 'secondary',
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusClasses[$appl->status] ?? 'secondary' }} fs-6">
                                            {{ ucfirst(str_replace('_', ' ', $appl->status)) }}
                                        </span>
                                    </td> 
                                    <td>{{ @$appl->member->membership_number }}</td>                                   
                                    <td>{!! $appl->action_buttons !!}</td>
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