@extends('layouts.core')

@section('title', 'Employee Management')
    
@section('content')
    @include('employees.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Edit Employee</h5>
            <div class="card-content p-2">
                {{ Form::model($employee, ['route' => ['employees.update', $employee], 'method' => 'PATCH']) }}
                    @include('employees.form')
                    <div class="text-center mt-2">
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                        {{ Form::submit('Submit', ['class' => 'btn btn-primary']) }}
                    </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@stop


