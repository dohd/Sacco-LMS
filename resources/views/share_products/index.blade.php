@extends('layouts.core')
@section('title', 'Share Products')

@section('content')
    @include('share_products.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>CODE#</th>
                            <th>NAME</th>
                            <th>SHARE CAPITAL ACC.</th>
                            <th>SHARE PREMIUM ACC.</th>
                            <th>UNIT VALUE</th>
                            <th>MAX. UNITS</th>
                            <th>MIN. UNITS</th> 
                            <th>STATUS</th> 
                            <th>ACTION</th>                          
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($shareProducts as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $shareProducts->count() == 1? '80px': '' }}">
                                        {{ $row->code }}
                                    </td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ '_' }}</td>
                                    <td>{{ '_' }}</td>
                                    <td>{{ $row->unit_value }}</td>
                                    <td>{{ $row->maximum_units }}</td>
                                    <td>{{ $row->minimum_units }}</td>
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