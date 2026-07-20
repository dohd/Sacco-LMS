@extends('layouts.core')

@section('title', 'Settings')
    
@section('content')
    @include('settings.header')
    {{ Form::open(['route' => 'settings.update', 'method' => 'POST', 'class' => 'form']) }}
        @include('settings.form')
        <div class="text-center">
            <a href="{{ route('home') }}" class="btn btn-secondary">Cancel</a>
            {{ Form::submit('Save Configurations', ['class' => 'btn btn-primary']) }}
        </div>
    {{ Form::close() }}
@stop
