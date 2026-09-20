@extends('layouts.core')
@section('title', 'Create | Savings Transactions')

@section('content')
    @include('savings_transactions.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'savings_transactions.store', 'method' => 'POST', 'id' => 'savingsTransactionForm']) }}
            @include('savings_transactions.form')
        {{ Form::close() }}
    </div>
@stop

@section('script')
@include('savings_transactions.form_js')
@endsection