@extends('layouts.core')

@section('title', 'View | Role Management')
    
@section('content')
    @include('roles.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Role Details</h5>
            <div class="card-content p-2">
                <table class="table table-bordered">
                    @php
                        $details = [
                            'Role' => $role->name,
                        ];
                    @endphp
                    @foreach ($details as $key => $val)
                        <tr>
                            <th width="30%">{{ $key }}</th>
                            <td>{{ $val}}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@stop
