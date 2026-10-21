@extends('layouts.core')
@section('title', 'Membership Management | Edit')

@section('content')
    @include('memberships.partial.header')
    @include('memberships.style')    
    {{ Form::model($membership, ['route' => ['memberships.update', $membership], 'method' => 'PATCH', 'id' => 'applicationForm', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate']) }} 
        @include('memberships.form')
    {{ Form::close() }}
@stop

@section('script')
@include('memberships.form_js')
@endsection