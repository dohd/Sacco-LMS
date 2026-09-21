@extends('layouts.core')
@section('title', 'Create | Savings Withdrawals')

@section('content')
    @include('savings_withdrawals.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'savings_withdrawals.store', 'method' => 'POST', 'id' => 'withdrawalForm']) }}
            @include('savings_withdrawals.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('savings_withdrawals.form_js')
@endsection