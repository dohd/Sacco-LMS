@extends('layouts.core')
@section('title', 'Create | User Management')
    
@section('content')
    @include('users.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'users.store', 'method' => 'POST', 'id' => 'usersForm']) }}
            @php
                $isEdit = isset($user);
            @endphp
            @include('users.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('users.form_js')
@endsection    
