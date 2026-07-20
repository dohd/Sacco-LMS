<div class="tab-pane fade profile-edit pt-3" id="profile-edit">
    <!-- Profile Edit Form -->
    {{ Form::open(['route' => array('users.update_active_profile', $user_profile), 'files' => true, 'method' => 'POST']) }}
      <div class="row mb-3">
        <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Profile Image</label>
        <div class="col-md-8 col-lg-9">
          {{ Form::file('profile_pic', ['class' => 'form-control', 'id' => 'profile_pic', 'accept' => '.png, .jpg, .jpeg' ]) }}
        </div>
      </div>

      <div class="row mb-3">
        <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
        <div class="col-md-8 col-lg-9">
          <input name="fullName" type="text" class="form-control" id="fullName" value="{{ $user_profile->name }}" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <label for="Job" class="col-md-4 col-lg-3 col-form-label">Role</label>
        <div class="col-md-8 col-lg-9">
          <input name="job" type="text" class="form-control" id="Job" value="{{ $role->name }}" disabled>
        </div>
      </div>

      <div class="row mb-3">
        <label for="Job" class="col-md-4 col-lg-3 col-form-label">Username<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="username" type="text" class="form-control" id="username" value="{{ $user_profile->username }}">
        </div>
      </div>

      <div class="row mb-3">
        <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="phone" type="text" class="form-control" id="Phone" value="{{ $user_profile->phone }}">
        </div>
      </div>

      <div class="row mb-3">
        <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="email" type="email" class="form-control" id="Email" value="{{ $user_profile->email }}">
        </div>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    {{ Form::close() }}
    <!-- End Profile Edit Form -->
  </div>