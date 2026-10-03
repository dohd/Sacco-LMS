@extends('layouts.core')
@section('title', 'Create | Share Accounts')

@section('content')
    @include('share_accounts.partial.header')
    <div class="container-fluid py-4">
        {{ Form::open(['route' => 'share_accounts.store', 'method' => 'POST', 'id' => 'shareAccountForm']) }}
            @include('share_accounts.form')
        {{ Form::close() }}
    </div>
@endsection


@section('script')
@include('share_accounts.form_js')
@endsection
