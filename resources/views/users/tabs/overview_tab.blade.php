<div class="tab-pane fade show active profile-overview" id="profile-overview">
  {{-- <h5 class="card-title">Details</h5> --}}
  @php
    $details = [
      'Full Name' => $user_profile->name,
      'Company' => @$user_profile->tenant->name,
      'Role' => $role->name,
      'Username' => $user_profile->username,
      'Phone' => $user_profile->phone,
      'Email' => $user_profile->email,
    ];
  @endphp
  @foreach ($details as $key => $value)
    <div class="row mt-2 ps-1">
      <div class="col-lg-3 col-md-4 label" style="font-weight:1000">{{ $key }}</div>
      <div class="col-lg-9 col-md-8 h6">{{ $value }}</div>
    </div>
  @endforeach
</div>