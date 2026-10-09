@extends('layouts.core')
@section('title', 'User Management')
    
@section('content')
    @include('users.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>NAME</th>
                            <th>PHONE</th>
                            <th>EMAIL</th>
                            <th>EMPLOYEE NO.</th>
                            <th>ROLE</th>
                            <th>STATUS</th>
                            <th>LAST LOGIN</th>
                            <th>ACTION</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $row)
                                <tr>
                                    <td scope="row" style="height: {{ $users->count() == 1? '80px': '' }}">{{ $row->name }}</td>
                                    <td>{{ $row->phone }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td>{{ $row->employee_number }}</td>
                                    <td>{{ optional($row->roles()->first())->name }}</td>
                                    <td>{!! $row->is_active_status_budge !!}</td>
                                    <td>{{ $row->last_login_at? dateFormat($row->last_login_at, 'd-M-Y H:i') : '' }}</td>
                                    <td>{!! $row->action_buttons !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('users.partial.status_modal')
@stop

@section('script')
<script>
    const formAttr = {url: '', status: 'Active'};
    $('table').on('click', '.modal-btn', function() {
        formAttr.url = $(this).attr('data-url');
        formAttr.status = $(this).text().replace(/\s+/g,'');
    });
    $('#status_modal').on('shown.bs.modal', function() {
        $(this).find('form').attr('action', formAttr.url);
        $(this).find('select#status').val((formAttr.status == 'Active'? 1 : 0));
    });
</script>
@stop