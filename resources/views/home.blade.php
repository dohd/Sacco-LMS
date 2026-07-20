@extends('layouts.core')
@section('title', 'Dashboard')

@section('content')
<main>
  <div class="pagetitle">
    <h1>Dashboard</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('home') }}">Home</a></li>
      </ol>
    </nav>
  </div>
  <!-- End Page Title -->

  <section class="section dashboard">
    <div class="row">
      <div class="col-lg-12">
        <div class="row">
          <!-- Activities Card -->
          <div class="col-md-12 col-12">
            <div class="card info-card sales-card">
              <div class="filter">
                <a class="icon" href="#" data-bs-toggle="dropdown" ><i class="bi bi-three-dots"></i></a>
              </div>
              <div class="card-body">
                <div class="m-5 text-center">
                  <h1>Welcome {{ auth()->user()->name }}</h1>
                  <h1 style="color: #4154f1">~ Dashboard ~</h1>
                </div>
              </div>
            </div>
          </div>
          <!-- End Activities Card -->
        </div>
      </div>
    </div>
  </section>
</main>
@stop
