@extends('layouts.core')
@section('title', 'Edit | Savings Withdrawals')

@section('content')
    @include('savings_withdrawals.partial.header')
    <div class="container-fluid">
        {{ Form::model($savingsWithdrawal, ['route' => ['savings_withdrawals.update', $savingsWithdrawal], 'method' => 'PATCH', 'id' => 'withdrawalForm']) }}
            @include('savings_withdrawals.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('savings_withdrawals.form_js')
@endsection