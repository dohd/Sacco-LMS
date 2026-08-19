@extends('layouts.core')
@section('title', 'Membership Management | Edit')

@section('content')
    @include('memberships.partial.header')
    <style>
        body {
            background-color: #f4f6f9;
        }

        .application-container {
            max-width: 1100px;
        }

        .form-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .form-card .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
            padding: 18px 22px;
        }

        .form-card .card-body {
            padding: 24px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-right: 10px;
            border-radius: 50%;
            background-color: #198754;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }

        .required-label::after {
            content: " *";
            color: #dc3545;
        }

        .document-preview {
            display: none;
            width: 100%;
            max-width: 180px;
            height: 130px;
            margin-top: 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: contain;
            background-color: #ffffff;
            padding: 4px;
        }

        .optional-section {
            display: none;
        }

        .application-header {
            background-color: #198754;
            color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .submit-section {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background-color: rgba(244, 246, 249, 0.95);
            padding: 15px 0;
        }
    </style>
    <div class="container application-container py-2">
        <div class="application-header">
            <p class="mb-0">
                Complete all required sections and upload the necessary supporting documents.
            </p>
        </div>

        {{ Form::model($membership, ['route' => ['memberships.update', $membership], 'method' => 'PATCH', 'id' => 'applicationForm', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate']) }} 
            @include('memberships.form')
        {{ Form::close() }}
    </div>    
@stop

@section('script')
    @include('memberships.form_js')
@endsection