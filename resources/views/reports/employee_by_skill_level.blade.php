@extends('layouts.core')

@section('title', 'Employee Report')
    
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Employee By Skill Level</h5>
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Skill Level</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($skillLevels as $i => $row)
                                <tr>
                                    <th scope="row">{{ $i+1 }}</th>
                                    <td>{{ $row->education_peak }}</td>
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
