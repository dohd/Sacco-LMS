@extends('layouts.core')
@section('title', 'Edit | Chart Of Accounts')

@section('content')
    @include('chart_of_accounts.partial.header')
    
    @php
        $isEdit = isset($chartOfAccount);
    @endphp

    {{ Form::open(['route' => ['chart_of_accounts.update', $chartOfAccount], 'method' => 'PATCH', 'id' => 'chartOfAccountForm']) }}
        @include('chart_of_accounts.form')
    {{ Form::close() }}
@endsection

@section('script')
@include('chart_of_accounts.form_js')          
@endsection
