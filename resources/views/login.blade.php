<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>@yield('title')</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  {{-- <link href="{{ asset('img/favicon.png') }}" rel="icon">
  <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon"> --}}

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">

  <!-- Custom CSS File -->
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

              <div class="d-flex justify-content-center py-4">
                  <!-- <img src="{{ asset('img/mwis-logo.png') }}" alt="logo"> -->
              </div>
              <!-- End Logo -->
  
              <div class="card mb-3">
                <div class="card-body">
                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center pb-0 fs-4">{{ config('app.name') }}</h5>
                    <p class="text-center small">Enter your credentials</p>
                  </div>
  
                  <!-- Login Form -->
                  <form action="{{ route('login') }}" method="POST" class="row g-3 needs-validation" novalidate>
                    @csrf
                    <div class="col-12">
                      <label for="email" class="form-label">Email</label>
                      <div class="input-group has-validation">
                        <span class="input-group-text" id="inputGroupPrepend">@</span>
                        <input type="text" name="email" class="form-control" id="yourUsername" required>
                        <div class="invalid-feedback">Please enter valid email!</div>
                      </div>
                    </div>
  
                    <div class="col-12">
                      <label for="yourPassword" class="form-label">Password</label>
                      <input type="password" name="password" class="form-control" id="yourPassword" required>
                      <div class="invalid-feedback">Please enter valid password!</div>
                    </div>
  
                    <div class="col-12">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" value="true" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                      </div>
                    </div>
                    <div class="col-12">
                      <button class="btn btn-primary w-100" type="submit">Login</button>
                    </div>
                    <div class="col-12 d-none">
                      <p class="small mb-0">Don't have account? <a href="{{ route('register') }}">Create an account</a></p>
                    </div>
                  </form>
                  <!-- End Login Form -->
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

  <!-- Vendor JS Files -->
  <script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery-3.6.1.min.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('js/main.js') }}"></script>
  <!-- Custom JS File  -->
  @include('layouts.config_js')
</body>

</html>
