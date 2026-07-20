@extends('layouts.core')
@section('header', '')
@section('sidebar', '')
@section('content', '')
@section('footer', '')

@section('title', 'Error 404')

<main>
  <div class="container">
    <section class="section error-404 min-vh-100 d-flex flex-column align-items-center justify-content-center">
      <h1>404</h1>
      <h2>The page you are looking for doesn't exist.</h2>
      <a class="btn" href="{{ route('home') }}">Back to home</a>
      <img src="{{ asset('img/not-found.svg') }}" class="img-fluid py-5" alt="Page Not Found">
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">Alloice @ Hackermode Inc.</a>
      </div>
    </section>
  </div>
</main>
