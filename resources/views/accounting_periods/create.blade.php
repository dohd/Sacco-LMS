@extends('layouts.core')
@section('title', 'Create | Accounting Periods Management')
    
@section('content')
    @include('accounting_periods.partial.header')
    @php
        $isEdit = isset($accountingPeriod);
        $isOpen = !$isEdit || $accountingPeriod->status === 'open';

        $statuses = [
            'open' => 'Open',
            'closed' => 'Closed',
            'locked' => 'Locked',
        ];
    @endphp

    <div class="container-fluid">
        @if($isEdit && !$isOpen)
            <div class="alert alert-warning">
                This accounting period is
                <strong>{{ ucfirst($accountingPeriod->status) }}</strong>
                and cannot be edited.

                @if($accountingPeriod->status === 'closed')
                    Use the period reopening workflow if reopening is permitted.
                @else
                    Locked periods should not be modified.
                @endif
            </div>
        @else
            {{ Form::open(['route' => 'accounting_periods.store', 'method' => 'POST', 'id' => 'accountingPeriodForm']) }}
                @include('accounting_periods.form')
            {{ Form::close() }}
        @endif
    </div>
@endsection


@section('script')
@include('accounting_periods.form_js')
@endsection
