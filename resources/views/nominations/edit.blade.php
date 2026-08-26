@extends('layouts.core')
@section('title', 'Nomination Management | Edit')

@section('content')
    @include('nominations.partial.header')
    <style>
        body {
            background: #f4f6f9;
        }

        .nomination-container {
            max-width: 1150px;
        }

        .form-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .form-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 18px 22px;
        }

        .form-card .card-body {
            padding: 24px;
        }

        .form-header {
            background: #198754;
            color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-right: 10px;
            border-radius: 50%;
            background: #198754;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .nominee-card,
        .witness-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .nominee-card .card-header,
        .witness-card .card-header {
            background: #f8f9fa;
            padding: 12px 16px;
        }

        .percentage-summary {
            position: sticky;
            top: 15px;
            z-index: 5;
        }

        .signature-preview {
            display: none;
            width: 100%;
            max-width: 220px;
            height: 120px;
            margin-top: 10px;
            padding: 4px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: contain;
            background: #ffffff;
        }

        .submit-section {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding: 15px 0;
            background: rgba(244, 246, 249, 0.95);
        }
    </style>
    <div class="container nomination-container py-2">
        <div class="form-header">
            <p class="mb-0">
                Nominate up to five beneficiaries and distribute the benefits so that the total allocation equals 100%.
            </p>
        </div>
        {{ Form::model($nomination, ['route' => ['nominations.update', $nomination], 'method' => 'PATCH', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate', 'id' => 'nominationForm']) }}
            @include('nominations.form')
        {{ Form::close() }}
    </div>
@stop

@section('script')
@include('nominations.form_js')
@endsection
