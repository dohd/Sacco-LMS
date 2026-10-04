@extends('layouts.core')
@section('title', 'Create | Share Transactions')

@section('content')
    @include('share_transactions.partial.header')
    <div class="container-fluid py-4"> 
        {{ Form::open(['route' => 'share_transactions.store', 'method' => 'POST', 'id' => 'shareTransactionForm']) }}
            @include('share_transactions.form')
        {{ Form::close() }} 
    </div> 
@endsection

@section('script')
@include('share_transactions.form_js')
@endsection
