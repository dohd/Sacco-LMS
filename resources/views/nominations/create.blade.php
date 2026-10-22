@extends('layouts.core')
@section('title', 'Create | Nomination Management')

@section('content')
    @include('nominations.partial.header')
    @include('nominations.style')
    {{ Form::open(['route' => 'nominations.store', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate', 'id' => 'nominationForm']) }}
        @include('nominations.form')
    {{ Form::close() }}
@stop

@section('script')
@include('nominations.form_js')
@endsection
