<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>
    @yield('title')
  </title>
  <meta content="Bima Setup" name="description">
  <meta content="Insurance, Bima" name="keywords">
  <meta content="Dojotech Solutions" name="author">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/quill/quill.snow.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/quill/quill.bubble.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/simple-datatables/style.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/select2/select2-4.0.13.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/vkurko-calendar-0.18.1/calendar.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/datepicker/datepicker.min.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">

  <!-- Custom CSS File -->
  <link href="{{ asset('css/core.css') }}" rel="stylesheet">

  <!-- Livewire Css -->
  @livewireStyles
</head>

<body>

  <!-- ======= Header ======= -->
  @section('header')
    @include('layouts.header')
  @show
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  @section('sidebar')
    @include('layouts.sidebar_menu')
  @show
  <!-- End Sidebar-->

  <div id="main" class="main">
    @include('layouts.flash_message')
    @yield('content')
  </div>

  <!-- End #main -->

  <!-- ======= Footer ======= -->
  @section('footer')
    @include('layouts.footer')
  @show
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('vendor/echarts/echarts.min.js') }}"></script>
  <script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
  <script src="{{ asset('vendor/simple-datatables/simple-datatables.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery-3.6.1.min.js') }}"></script>
  <script src="{{ asset('vendor/select2/select2-4.0.13.min.js') }}"></script>
  <script src="{{ asset('vendor/vkurko-calendar-0.18.1/calendar.min.js') }}"></script>
  <script src="{{ asset('vendor/validator/jquery.validate.min.js') }}"></script>
  <script src="{{ asset('vendor/datepicker/datepicker.min.js') }}"></script>
  <script src="{{ asset('vendor/accounting/accounting.min.js') }}"></script>
  <script src="{{ asset('vendor/form-repeater/jquery.repeater.min.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('js/main.js') }}"></script>

  <!-- Custom JS File  -->
  @include('layouts.config_js')
  @yield('script')

  <!-- Livewire JS -->
  {{-- @livewireScripts --}}
</body>

</html>