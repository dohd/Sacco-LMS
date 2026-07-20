@extends('layouts.core')

@section('title', 'Role Management')
    
@section('content')
    @include('roles.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <table class="table table-borderless datatable">
                    <thead>
                      <tr>
                        <th scope="col">#No</th>
                        <th scope="col">Role</th>
                        <th scope="col">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $i => $role)
                            <tr>
                                <th scope="row">{{ $i+1 }}</th>
                                <td>{{ $role->name }}</td>
                                <td>{!! $role->action_buttons !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
@stop
