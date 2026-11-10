@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center"><div class="col-lg-6">
        <div class="card">
            <div class="card-header">KYC verification in progress</div>
            <div class="card-body">
                <h5>{{ $member->full_name }}</h5>
                <p>Membership number: <strong>{{ $member->membership_number }}</strong></p>
                <p>Your membership details were submitted on {{ $member->kyc_submitted_at->format('d M Y') }}. An administrator must verify them before you can use member services.</p>
                <span class="badge bg-warning text-dark">Verification pending</span>
            </div>
        </div>
    </div></div>
</div>
@endsection
