@extends('layouts.core')
@section('title', 'Create | Journal Entries')
    
@section('content')
    @include('journal_entries.partial.header')
    @php
        $isEdit = isset($journalEntry);
        $lines = old('lines', $journalEntry->lines ?? [
            ['account_id' => '', 'debit' => '', 'credit' => '']
        ]);
    @endphp

    {{ Form::open(['route' => 'journal_entries.store', 'method' => 'POST', 'id' => 'journalEntryForm']) }}
        @include('journal_entries.form')
    {{ Form::close() }}
@endsection

@section('script')
@include('journal_entries.form_js')
@endsection