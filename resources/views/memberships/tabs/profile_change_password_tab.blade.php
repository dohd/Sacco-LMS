<div class="tab-pane fade pt-3" id="profile-change-password">
    <!-- Change Password Form -->
    {{ Form::open(['route' => array('users.update_active_profile', $user_profile), 'method' => 'POST']) }}
      <div class="row mb-3">
        <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label" style="color:rgba(1, 41, 112, 0.6); font-weight:600">Current Password<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="current_password" type="password" class="form-control">
        </div>
      </div>

      <div class="row mb-3">
        <label for="newPassword" class="col-md-4 col-lg-3 col-form-label" style="color:rgba(1, 41, 112, 0.6); font-weight:600">New Password<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="password" type="password" class="form-control">
        </div>
      </div>

      <div class="row mb-3">
        <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label" style="color:rgba(1, 41, 112, 0.6); font-weight:600">Confirm Password<span class="text-danger">*</span></label>
        <div class="col-md-8 col-lg-9">
          <input name="confirm_password" type="password" class="form-control">
        </div>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-primary">Change Password</button>
      </div>
    {{ Form::close() }}
    <!-- End Change Password Form -->
  </div>
  