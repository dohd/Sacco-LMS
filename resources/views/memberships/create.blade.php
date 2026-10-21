@extends('layouts.core')

@section('title', 'Membership Management | Create')

@section('content')
    @include('memberships.partial.header')
    @include('memberships.style') 
    {{ Form::open(['route' => 'memberships.store', 'method' => 'POST', 'id' => 'applicationForm', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate']) }} 
        @include('memberships.form')
    {{ Form::close() }}
@stop

@section('script')
@include('memberships.form_js')
@endsection