@extends('layouts.core')
@section('title', 'Edit | Loan Repayments')

@section('content')
    @include('loan_repayments.partial.header')
    <div class="container-fluid">
        {{ Form::model($loanRepayment, ['route' => ['loan_repayments.update', $loanRepayment], 'method' => 'PATCH', 'enctype' => 'multipart/form-data', 'id' => 'loanRepaymentForm']) }}
            @include('loan_repayments.form')
        {{ Form::close() }}
    </div>
@stop

@section('script')
@include('loan_repayments.form_js')
@endsection
