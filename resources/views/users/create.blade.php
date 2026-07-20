@extends('layouts.core')

@section('title', 'Create | User')
    
@section('content')
    @include('users.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">User Details</h5>
            <div class="card-content p-2">
                {{ Form::open(['route' => 'users.store', 'method' => 'POST', 'class' => 'form']) }}
                    @include('users.form')
                    <div class="text-center">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                        {{ Form::submit('Submit', ['class' => 'btn btn-primary']) }}
                    </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@stop
