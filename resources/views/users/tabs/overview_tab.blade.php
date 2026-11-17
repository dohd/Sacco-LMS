<div class="tab-pane fade show active profile-overview" id="profile-overview">
  {{-- <h5 class="card-title">Details</h5> --}}
  @php
    $details = [
      'Full Name' => $user->name,
      'Company' => @$user->tenant->name,
      'Role' => $role->name,
      'Email' => $user->email,
      'Phone' => $user->phone,
    ];
  @endphp
  @foreach ($details as $key => $value)
    <div class="row mt-2 ps-1">
      <div class="col-lg-3 col-md-4 label" style="font-weight:1000">{{ $key }}</div>
      <div class="col-lg-9 col-md-8 h6">{{ $value }}</div>
    </div>
  @endforeach
</div>
