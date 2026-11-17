@extends('layouts.core')

@section('title', 'Complete Membership Details')

@section('content')
    @include('memberships.style')

    <div class="container application-container py-3">
        <div class="application-header">
            <h1 class="h3 mb-2">{{ $member->kyc_submitted_at ? 'Update your membership details' : 'Complete your membership application' }}</h1>
            <p class="mb-0">Provide employment details, business details, or both. Contribution details are optional. Replace either ID file only if you need to correct it.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                Please review the highlighted fields and submit the form again.
                @error('employment_or_business')
                    <div>{{ $message }}</div>
                @enderror
            </div>
        @endif

        {{ Form::open(['route' => 'member.kyc.store', 'method' => 'POST', 'id' => 'applicationForm', 'enctype' => 'multipart/form-data', 'novalidate' => 'novalidate']) }}
            @include('memberships.form')
        {{ Form::close() }}
    </div>
@endsection

@section('script')
    @include('memberships.form_js')
@endsection
