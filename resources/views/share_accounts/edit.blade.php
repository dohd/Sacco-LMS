@extends('layouts.core')
@section('title', 'Edit | Share Accounts')

@section('content')
    @include('share_accounts.partial.header')
    <div class="container-fluid">
        {{ Form::model($shareAccount, ['route' => ['share_accounts.update', $shareAccount], 'method' => 'PATCH', 'id' => 'shareAccountForm']) }}
            @include('share_accounts.form')
        {{ Form::close() }}
    </div>    
@stop

@section('script')
@include('share_accounts.form_js')
@endsection