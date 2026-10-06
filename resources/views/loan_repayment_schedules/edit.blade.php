@extends('layouts.core')
@section('title', 'Edit | Loan Repayment Schedules')

@section('content')
    @include('loan_repayment_schedules.partial.header')
    @php
        $isEdit = isset($schedule) && $schedule->exists;
        $loanId = old('loan_id', $schedule->loan_id ?? request('loan_id'));
        $memberId = old('member_id', $schedule->member_id ?? request('member_id'));
    @endphp
    <div class="container-fluid py-4">
        {{ Form::model($schedule, ['route' => ['loan_repayment_schedules.update', $schedule], 'method' => 'PATCH', 'id' => 'scheduleForm']) }}
            @include('loan_repayment_schedules.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
@include('loan_repayment_schedules.form_js')
@endsection