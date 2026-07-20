<li class="nav-item dropdown pe-4">
    <a
      class="nav-link nav-profile d-flex align-items-center pe-0"
      href="#"
      data-bs-toggle="dropdown"
    >
      {{-- <img 
        src="{{ route('storage.file_render', 'images,user_profiles,' . auth()->user()->profile_pic) }}" 
        onerror="this.onerror=null; this.src='{{ asset('img/profile-img.jpeg') }}'"
        alt="profile-picture" 
        class="rounded-circle"
      > --}}
      <span class="d-none d-md-block dropdown-toggle ps-2">{{ auth()->user()->name }}</span>
    </a>
    <!-- End Profile Image Icon -->
    
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile" style="min-width:180px;">
      {{-- <li class="dropdown-header">
        <span>{{ auth()->user()->roles()->first() ? auth()->user()->roles()->first()->name : '' }}</span>
      </li>
      <li>
        <hr class="dropdown-divider" />
      </li> --}}
      <li>
        <a class="dropdown-item d-flex align-items-center" href="{{ route('users.active_profile') }}">
          <i class="bi bi-person"></i>
          <span>My Profile</span>
        </a>
      </li>
      <li>
        <hr class="dropdown-divider" />
      </li>
      <li>
        <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}">
          <i class="bi bi-box-arrow-right"></i>
          <span>Sign Out</span>
        </a>
      </li>
    </ul>
    <!-- End Profile Dropdown Items -->
  </li>