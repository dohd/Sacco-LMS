@extends('layouts.app')

@section('content')
@php
    $oldNationalId = substr((string) $application->national_id, 0, 15) === 'PILOT-PENDING-' ? '' : $application->national_id;
    $oldPhone = substr((string) $application->phone, 0, 15) === 'PILOT-PENDING-' ? '' : $application->phone;
@endphp
<div class="container py-4">
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="card">
            <div class="card-header">Complete your membership application details</div>
            <div class="card-body">
                <p>Provide the minimum identity, contact, residential, and next of kin details below and upload both sides of your national ID. An administrator will review these details before your member workflows are enabled.</p>
                @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <form method="POST" action="{{ route('member.kyc.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="date_of_birth">Date of birth</label><input class="form-control" type="date" id="date_of_birth" name="date_of_birth" max="{{ now()->toDateString() }}" value="{{ old('date_of_birth', $application->date_of_birth ? \Carbon\Carbon::parse($application->date_of_birth)->format('Y-m-d') : '') }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="place_of_birth">Place of birth</label><input class="form-control" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth', $application->place_of_birth) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="national_id">National ID number</label><input class="form-control" id="national_id" name="national_id" value="{{ old('national_id', $oldNationalId) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="phone">Phone number</label><input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone', $oldPhone) }}" required></div>
                        <div class="col-12"><label class="form-label">Login email</label><input class="form-control" value="{{ auth()->user()->email }}" readonly></div>
                        <div class="col-12"><label class="form-label" for="current_address">Current address (optional)</label><textarea class="form-control" id="current_address" name="current_address" rows="2">{{ old('current_address', $application->current_address) }}</textarea></div>
                        <div class="col-12"><label class="form-label" for="residential_address">Residential address</label><textarea class="form-control" id="residential_address" name="residential_address" rows="3" required>{{ old('residential_address', $application->residential_address) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label" for="next_of_kin_name">Next of kin full name</label><input class="form-control" id="next_of_kin_name" name="next_of_kin_name" value="{{ old('next_of_kin_name', $application->next_of_kin_name) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="next_of_kin_id">Next of kin ID number</label><input class="form-control" id="next_of_kin_id" name="next_of_kin_id" value="{{ old('next_of_kin_id', $application->next_of_kin_id) }}" required></div>
                        <div class="col-md-6"><label class="form-label" for="next_of_kin_relationship">Relationship</label><select class="form-select" id="next_of_kin_relationship" name="next_of_kin_relationship" required><option value="">Select relationship</option>@foreach (['spouse','parent','child','sibling','relative','guardian','other'] as $relationship)<option value="{{ $relationship }}" @if(old('next_of_kin_relationship', $application->next_of_kin_relationship) === $relationship) selected @endif>{{ ucfirst(str_replace('_', ' ', $relationship)) }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label" for="national_id_front">National ID front (JPG, PNG, or PDF)</label><input class="form-control" type="file" id="national_id_front" name="national_id_front" accept=".jpg,.jpeg,.png,.pdf" required></div>
                        <div class="col-md-6"><label class="form-label" for="national_id_back">National ID back (JPG, PNG, or PDF)</label><input class="form-control" type="file" id="national_id_back" name="national_id_back" accept=".jpg,.jpeg,.png,.pdf" required></div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Submit details for KYC review</button>
                </form>
            </div>
        </div>
    </div></div>
</div>
@endsection
