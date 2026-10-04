@extends('layouts.core')
@section('title', 'Create | Loan Disbursements')

@section('content')
    @include('loan_disbursements.partial.header')
    <div class="container-fluid">
        {{ Form::open(['route' => 'loan_disbursements.store', 'method' => 'POST', 'enctype' => 'multipart/form-data', 'id' => 'loanDisbursementForm']) }}
            @include('loan_disbursements.form')
        {{ Form::close() }}
    </div>    
@stop

@section('script')
@include('loan_disbursements.form_js')
@endsection
