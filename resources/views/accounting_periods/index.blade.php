@extends('layouts.core')
@section('title', 'Accounting Periods Management')
    
@section('content')
    @include('accounting_periods.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>NAME</th>
                            <th>FINANCIAL YEAR</th>
                            <th>START DATE</th>
                            <th>END DATE</th>
                            <th>STATUS</th>
                            <th>CLOSED BY</th>
                            <th>CLOSED AT</th>
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ([] as $i => $user)
                                <tr>
                                    <th scope="row" style="height: {{ count($accounting_periods) == 1? '80px': '' }}">{{ $i+1 }}</th>
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
    //
</script>
@stop