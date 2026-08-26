@extends('layouts.core')
@section('title', 'Nomination Management')
    
@section('content')
    @include('nominations.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>MEMBERSHIP#</th>
                            <th>MEMBER</th>
                            <th>DECLARATION DATE</th>
                            <th>STATUS</th> 
                            <th>NOMINEES</th> 
                            <th>WITNESSES</th> 
                            <th>ACTION</th>                            
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($nominations as $nomination)
                                <tr>
                                    <td style="height: {{ $nominations->count() == 1? '80px': '' }}">{{ @$nomination->member->membership_number }}</td>
                                    <td>{{ @$nomination->member->full_name }}</td>
                                    <td>{{ dateFormat($nomination->declaration_date) }}</td>
                                    <td>
                                        @php
                                            $statusClasses = [                                                
                                                'pending' => 'warning',                                                
                                                'approved' => 'success',
                                                'rejected' => 'danger',                                                
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusClasses[$nomination->status] ?? 'secondary' }} fs-6">
                                            {{ ucfirst(str_replace('_', ' ', $nomination->status)) }}
                                        </span>
                                    </td> 
                                    <td>{{ @$nomination->nominees->count() }}</td>
                                    <td>{{ @$nomination->witnesses->count() }}</td>
                                    <td>{!! $nomination->action_buttons !!}</td>
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