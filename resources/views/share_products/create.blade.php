@extends('layouts.core')
@section('title', 'Create | Share Products')

@section('content')
    @include('share_products.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'share_products.store', 'method' => 'POST', 'id' => 'shareProductForm']) }}
            @include('share_products.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('share_products.form_js')
@endsection