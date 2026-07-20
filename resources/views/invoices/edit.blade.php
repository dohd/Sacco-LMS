@extends('layouts.core')

@section('title', 'Invoice Management')
    
@section('content')
    @include('invoices.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Edit Invoice</h5>
            <div class="card-content p-2">
                {{ Form::model($invoice, ['route' => ['invoices.update', $invoice], 'method' => 'PATCH']) }}
                    @include('invoices.form')
                    <div class="text-center mt-2">
                        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                    </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@stop


