@extends('layouts.core')

@section('title', 'User Profile')

@section('content')
  <div class="pagetitle">
    <h1>Profile</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active"><a href="{{ route('users.active_profile') }}">User Profile</a></li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section profile">
    <div class="row">
      <div class="col-xl-4">
        <div class="card">
          <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
            <div>
              <img 
                src="{{-- route('storage.file_render', 'images,user_profiles,' . $user_profile->profile_pic) --}}" 
                onerror="this.onerror=null; this.src='{{ asset('img/profile-img.jpeg') }}'"
                alt="profile-picture" 
                class="rounded-circle"
              >
              @if ($user_profile->profile_pic)
                <span class="float-end del" style="cursor: pointer;" data-toggle="tooltip" title="Remove profile picture">
                  <i class="bi bi-trash text-danger icon-xs"></i>
                </span>
              @endif
              <h2>{{ $user_profile->username }}</h2>
              <h3>{{ $role->name }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card">
          <div class="card-body pt-3">
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Overview</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
              </li>
              {{-- <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
              </li> --}}
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change Password</button>
              </li>
            </ul>
            <div class="tab-content pt-2">
              <!-- Overview -->
              @include('users.tabs.overview_tab')
              <!-- Profile Edit -->
              @include('users.tabs.profile_edit_tab')
              <!-- Profile Change Password -->
              @include('users.tabs.profile_change_password_tab')
            </div>
            <!-- End Bordered Tabs -->
          </div>
        </div>
      </div>
    </div>
  </section>
@stop

@section('script')
<script>
  $(document).on('click', '.del', function() {
    if (confirm('Are you sure?')) {
      $.post("{{ route('users.delete_profile_pic', $user_profile) }}")
      .done((data) => flashMessage(data))
      .catch((data) => flashMessage(data));
    }
  });
</script>
@endsection
