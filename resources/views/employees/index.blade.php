@extends('layouts.core')

@section('title', 'Employee Management')
    
@section('content')
    @include('employees.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Employee Management</h5>
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>PF No.</th>
                                <th>ID No.</th>                                
                                <th>Full Name</th>
                                <th>Designation</th>
                                <th>Work County</th>
                                <th>Engagement Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $i => $row)
                                <tr>
                                    <th scope="row">{{ $i+1 }}</th>
                                    <td>{{ $row->payroll_no }}</td>
                                    <td>{{ $row->id_no }}</td>
                                    <td>{{ implode(" ", [$row->surname, $row->first_name, $row->other_name])  }}</td>
                                    <td>{{ $row->job_desig }}</td>
                                    <td>{{ $row->work_county }}</td>
                                    <td>{{ $row->engagement_type }}</td>
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
