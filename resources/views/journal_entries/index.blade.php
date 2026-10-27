@extends('layouts.core')
@section('title', 'Create | Journal Entries')
    
@section('content')
    @include('journal_entries.partial.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content p-2">
                <table class="table table-borderless datatable">
                    <thead>
                      <tr>
                        <th>ENTRY NO#</th>
                        <th>TRANS. DATE</th>
                        <th>POSTING DATE</th>
                        <th>STATUS</th>
                        <th>CREATED BY</th>
                        <th>CREATED AT</th>
                        <th>ACTION</th>
                      </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $row)
                            <tr>
                                <td style="height: {{ $entries->count() == 1? '80px': '' }}">{{ $row->entry_number }}</td>
                                <td>{{ dateFormat($row->transaction_date) }}</td>
                                <td>{{ dateFormat($row->posting_date) }}</td>
                                <td>{{ $row->status }}</td>
                                <td>{{ @$row->creator->name }}</td>
                                <td>{{ dateFormat($row->created_at) }}</td>
                                <td>{!! $row->action_buttons !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                  </table>
            </div>
        </div>
    </div>
@stop
