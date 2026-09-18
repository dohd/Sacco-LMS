@extends('layouts.core')
@section('title', 'Edit | Savings Accounts')

@section('content')
    @include('savings_accounts.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => ['savings_accounts.update', $savingsAccount], 'method' => 'PATCH', 'id' => 'savingsAccountForm']) }}
            @include('savings_accounts.form')
        {{ Form::close() }}
    </div>    
@stop

@section('script')
@include('savings_accounts.form_js')
@endsection