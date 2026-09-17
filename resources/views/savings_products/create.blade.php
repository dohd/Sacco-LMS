@extends('layouts.core')
@section('title', 'Create | Savings Products')

@section('content')
    @include('savings_products.partial.header')
    <div class="container-fluid py-3">
        @php
            $savingsProduct = $savingsProduct ?? null;
            $editing = isset($savingsProduct);
            $booleanValue = function ($field, $default = false) use ($savingsProduct) {
                return old($field, @$savingsProduct->{$field} ?? $default) ? 1 : 0;
            };
        @endphp

        {{ Form::open(['route' => 'savings_products.store', 'method' => 'POST', 'novalidate' => 'novalidate', 'id' => 'savingsProductForm']) }}
            @include('savings_products.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('savings_products.form_js')
@endsection