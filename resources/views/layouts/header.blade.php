<header id="header" class="header fixed-top d-flex align-items-center">
  <div class="d-flex align-items-center justify-content-between">
    <a href="{{ route('home') }}" class="logo d-flex align-items-center">
      <!-- <img src="{{ asset('img/mwis-logo.png') }}" alt="logo" /> -->
      <span class="d-none d-lg-block">{{ config('app.name') }}</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>
  <!-- End Logo -->

  <div class="search-bar">
    <form
      class="search-form d-flex align-items-center"
      {{-- method="POST" --}}
      action="#"
      disabled
    >
      <input
        type="text"
        name="query"
        placeholder="Search"
        title="Enter search keyword"
      />
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div>
  <!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li>
      <!-- End Search Icon-->

      <!-- Profile Nav -->
      @include('layouts.profile_popover')
      <!-- End Profile Nav -->
    </ul>
  </nav>
  <!-- End Icons Navigation -->
</header>
