@extends('layouts.core')
@section('title', 'Edit | Share Products')

@section('content')
    @include('share_products.partial.header')
    <div class="container-fluid">
        {{ Form::model($shareProduct, ['route' => ['share_products.update', $shareProduct], 'method' => 'PATCH', 'id' => 'shareProductForm']) }}
            @include('share_products.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('share_products.form_js')
@endsection