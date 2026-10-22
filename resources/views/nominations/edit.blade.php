@extends('layouts.core')
@section('title', 'Edit | Nomination Management')

@section('content')
    @include('nominations.partial.header')
    @include('nominations.style')
    {{ Form::model($nomination, ['route' => ['nominations.update', $nomination], 'method' => 'PATCH', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate', 'id' => 'nominationForm']) }}
        @include('nominations.form')
    {{ Form::close() }}
@stop

@section('script')
@include('nominations.form_js')
@endsection
