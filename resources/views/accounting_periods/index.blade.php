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
                            @foreach ($periods as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $periods->count() == 1? '80px': '' }}">{{ $row->name }}</td>
                                    <td>{{ $row->financial_year }}</td>
                                    <td>{{ dateFormat($row->start_date) }}</td>
                                    <td>{{ dateFormat($row->end_date) }}</td>
                                    <td>{{ $row->status }}</td>
                                    <td>{{ @$row->closedBy->name }}</td>
                                    <td>{{ '' }}</td>
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
    //
</script>
@stop