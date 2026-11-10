@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card">
                <div class="card-header">Finish setting up your member login</div>
                <div class="card-body">
                    <p>For your first login, you used your import membership number as both username and password. Set your email and a new password below. You will use them for future logins.</p>
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('member.credentials.update') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">New password</label>
                            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
                            <small class="text-muted">Use at least 10 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Confirm new password</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Save login credentials</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
