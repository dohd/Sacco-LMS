@extends('layouts.core')

@section('title', 'Employee Report')
    
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Employee By Designation</h5>
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Designation</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($designations as $i => $row)
                                <tr>
                                    <th scope="row">{{ $i+1 }}</th>
                                    <td>{{ $row->job_desig }}</td>
                                    <td>{{ $row->count }}</td>
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
