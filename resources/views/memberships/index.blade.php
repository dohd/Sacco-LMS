@extends('layouts.core')

@section('title', 'User Management')
    
@section('content')
    @include('users.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <div class="table-responsive">
                    <table class="table table-borderless datatable">
                        <thead>
                          <tr>
                            <th>#No</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $i => $user)
                                <tr>
                                    <th scope="row" style="height: {{ count($users) == 1? '80px': '' }}">{{ $i+1 }}</th>
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