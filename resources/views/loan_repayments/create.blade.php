@extends('layouts.core')
@section('title', 'Create | Loan Repayments')

@section('content')
    @include('loan_repayments.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'loan_repayments.store', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'loanRepaymentForm']) }}
            @include('loan_repayments.form')
        {{ Form::close() }}
    </div>
@stop

@section('script')
@include('loan_repayments.form_js')
@endsection
