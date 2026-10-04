@extends('layouts.core')
@section('title', 'Edit | Loan Disbursements')

@section('content')
    @include('loan_disbursements.partial.header')
    <div class="container-fluid">
        {{ Form::model($loanDisbursement, ['route' => ['loan_disbursements.update', $loanDisbursement], 'method' => 'PATCH', 'enctype' => 'multipart/form-data', 'id' => 'loanDisbursementForm']) }}
            @include('loan_disbursements.form')
        {{ Form::close() }}
    </div>    
@stop

@section('script')
@include('loan_disbursements.form_js')
@endsection
