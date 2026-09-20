@extends('layouts.core')
@section('title', 'Edit | Savings Transactions')

@section('content')
    @include('savings_transactions.partial.header')
    <div class="container-fluid">
        {{ Form::model($savingsTransaction, ['route' => ['savings_transactions.update', $savingsTransaction], 'method' => 'PATCH', 'id' => 'savingsTransactionForm']) }}
            @include('savings_transactions.form')
        {{ Form::close() }}
    </div>
@stop

@section('script')
@include('savings_transactions.form_js')
@endsection