@extends('layouts.core')
@section('title', 'Edit | User Management')
    
@section('content')
    @include('users.partial.header')
    <div class="container-fluid">
        {{ Form::model($user, ['route' => ['users.update', $user], 'method' => 'PATCH', 'id' => 'usersForm']) }}
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
