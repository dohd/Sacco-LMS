<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Set Up Member Login | {{ saccoSetting('SACCO_NAME', config('app.name')) }}</title>

    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/core.css') }}" rel="stylesheet">
</head>

<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4 pt-0">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            @include('layouts.flash_message')

                            <div class="d-flex justify-content-center py-4"></div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">{{ saccoSetting('SACCO_NAME', config('app.name')) }}</h5>
                                        <p class="text-center small">Set up your member login</p>
                                    </div>

                                    <p class="small">Enter your email address and create a new password. You will use these credentials for future logins.</p>

                                    @if ($errors->any())
                                        <div class="alert alert-danger" role="alert">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('member.credentials.update') }}" class="row g-3 needs-validation" novalidate>
                                        @csrf

                                        <div class="col-12">
                                            <label for="email" class="form-label">Email address</label>
                                            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email" required autofocus>
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                            @error('email')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="password" class="form-label">New password</label>
                                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="{{ saccoSetting('PASSWORD_MIN_LENGTH', 10) }}" required>
                                            <div class="form-text">Use at least {{ saccoSetting('PASSWORD_MIN_LENGTH', 10) }} characters.</div>
                                            <div class="invalid-feedback">Please enter a new password.</div>
                                            @error('password')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="password_confirmation" class="form-label">Confirm new password</label>
                                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="{{ saccoSetting('PASSWORD_MIN_LENGTH', 10) }}" required>
                                            <div class="invalid-feedback">Please confirm your new password.</div>
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Save login credentials</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="credits">
                                Designed by <a href="#">Dojotech Solutions Ltd</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery-3.6.1.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    @include('layouts.config_js')
</body>

</html>
