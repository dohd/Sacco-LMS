@extends('layouts.core')

@section('title', 'Create | User Management')
    
@section('content')
    @include('users.partial.header')
    @php
        $isEdit = isset($user);
    @endphp

    <div class="container-fluid">
        <form method="POST"
              action="{{ $isEdit
                  ? route('users.update', $user->id)
                  : route('users.store') }}">

            @csrf

            @if($isEdit)
                @method('PUT')
            @endif


            {{-- =====================================================
                 USER INFORMATION
            ====================================================== --}}
            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        User Information
                    </h6>
                </div>

                <div class="card-body">

                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Full Name
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   maxlength="255"
                                   required
                                   value="{{ old(
                                       'name',
                                       $isEdit ? $user->name : ''
                                   ) }}"
                                   placeholder="Enter user's full name">

                        </div>


                        {{-- Employee Number --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Employee Number
                            </label>

                            <input type="text"
                                   name="employee_number"
                                   class="form-control"
                                   maxlength="255"
                                   value="{{ old(
                                       'employee_number',
                                       $isEdit ? $user->employee_number : ''
                                   ) }}"
                                   placeholder="e.g. EMP-001">

                        </div>


                        {{-- Email --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Email Address
                                <span class="text-danger">*</span>
                            </label>

                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   maxlength="255"
                                   required
                                   value="{{ old(
                                       'email',
                                       $isEdit ? $user->email : ''
                                   ) }}"
                                   placeholder="user@example.com">

                        </div>


                        {{-- Phone --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Phone Number
                            </label>

                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   maxlength="255"
                                   value="{{ old(
                                       'phone',
                                       $isEdit ? $user->phone : ''
                                   ) }}"
                                   placeholder="Phone number">

                        </div>

                    </div>

                </div>

            </div>



            {{-- =====================================================
                 ROLES & ACCESS
            ====================================================== --}}
            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <h6 class="mb-0">
                        Roles & Access
                    </h6>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-8">

                            <label class="form-label">
                                User Roles
                                <span class="text-danger">*</span>
                            </label>

                            @php
                                $selectedRoles = old(
                                    'roles',
                                    $isEdit ? $user->roles->pluck('id')->toArray() : []
                                );
                            @endphp

                            <select name="roles[]"
                                    id="roles"
                                    class="form-select"                                    
                                    required>

                                @foreach($roles as $role)

                                    <option value="{{ $role->id }}"
                                        {{ in_array($role->id, $selectedRoles) ? 'selected' : '' }}>

                                        {{ $role->name }}

                                    </option>

                                @endforeach

                            </select>

                            <small class="text-muted">
                                Select one or more roles for this user.
                            </small>

                        </div>

                        <div class="col-md-4">

                            <div class="alert alert-light border mb-0">

                                <i class="fa fa-shield-alt me-2"></i>

                                <strong>Access Control</strong>

                                <div class="small text-muted mt-1">
                                    User permissions are determined by the
                                    roles assigned to this account.
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 PASSWORD
            ====================================================== --}}
            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <h6 class="mb-0">
                        Security
                    </h6>

                </div>

                <div class="card-body">

                    <div class="row g-3">

                        {{-- Password --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Password

                                @if(!$isEdit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            <div class="input-group">

                                <input type="password"
                                       name="password"
                                       id="password"
                                       class="form-control"
                                       {{ !$isEdit ? 'required' : '' }}
                                       minlength="8"
                                       placeholder="{{ $isEdit
                                           ? 'Leave blank to keep current password'
                                           : 'Enter password' }}">

                                <button type="button"
                                        class="btn btn-outline-secondary"
                                        id="togglePassword">

                                    <i class="fa fa-eye"></i>

                                </button>

                            </div>

                            @if($isEdit)

                                <small class="text-muted">
                                    Leave blank if you do not want to change
                                    the current password.
                                </small>

                            @else

                                <small class="text-muted">
                                    Password must contain at least 8 characters.
                                </small>

                            @endif

                        </div>


                        {{-- Confirm Password --}}
                        <div class="col-md-6">

                            <label class="form-label">
                                Confirm Password

                                @if(!$isEdit)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            <input type="password"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   class="form-control"
                                   {{ !$isEdit ? 'required' : '' }}
                                   minlength="8"
                                   placeholder="Confirm password">

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 ACCOUNT STATUS
            ====================================================== --}}
            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <h6 class="mb-0">
                        Account Status
                    </h6>

                </div>

                <div class="card-body">

                    <div class="form-check form-switch">

                        <input type="hidden"
                               name="is_active"
                               value="0">

                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="form-check-input"
                               id="is_active"
                               {{ old(
                                   'is_active',
                                   $isEdit ? $user->is_active : true
                               ) ? 'checked' : '' }}>

                        <label class="form-check-label"
                               for="is_active">

                            <strong>Active Account</strong>

                            <div class="text-muted small">
                                Active users can log into the system.
                            </div>

                        </label>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                 LOGIN INFORMATION
            ====================================================== --}}
            @if($isEdit)

                <div class="card shadow-sm mb-4">

                    <div class="card-header bg-white">

                        <h6 class="mb-0">
                            Login Information
                        </h6>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="text-muted small">
                                    Last Login
                                </div>

                                <div>

                                    @if($user->last_login_at)

                                        {{ $user->last_login_at->format('d M Y H:i') }}

                                    @else

                                        Never logged in

                                    @endif

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="text-muted small">
                                    User ID
                                </div>

                                <div>
                                    #{{ $user->id }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- Security Notice --}}
            <div class="alert alert-info mb-4">

                <i class="fa fa-shield-alt me-2"></i>

                <strong>Security:</strong>

                User passwords are securely hashed and are never displayed
                after being saved.

            </div>


            {{-- Actions --}}
            <div class="d-flex justify-content-end gap-2 mb-5">

                <a href="{{ $isEdit
                    ? route('users.show', $user->id)
                    : route('users.index') }}"
                   class="btn btn-light">

                    Cancel

                </a>

                <button type="submit"
                        class="btn btn-primary"
                        id="saveBtn">

                    <i class="fa fa-save"></i>

                    {{ $isEdit
                        ? 'Update User'
                        : 'Create User' }}

                </button>

            </div>

        </form>

    </div>

@endsection


@section('script')
<script>
$(function () {

    /*
     * Show / hide password.
     */
    $('#togglePassword').on('click', function () {

        const input = $('#password');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {

            input.attr('type', 'text');

            icon.removeClass('fa-eye')
                .addClass('fa-eye-slash');

        } else {

            input.attr('type', 'password');

            icon.removeClass('fa-eye-slash')
                .addClass('fa-eye');

        }
    });


    /*
     * Confirm password.
     */
    $('#password_confirmation').on('input', function () {

        const password = $('#password').val();
        const confirmation = $(this).val();

        if (password && confirmation && password !== confirmation) {

            this.setCustomValidity(
                'Passwords do not match.'
            );

        } else {

            this.setCustomValidity('');
        }
    });


    /*
     * Prevent double submission.
     */
    $('form').on('submit', function () {

        const button = $('#saveBtn');

        if (button.data('submitted')) {
            return false;
        }

        button.data('submitted', true);

        button.prop('disabled', true)
            .html(
                '<i class="fa fa-spinner fa-spin"></i> Saving...'
            );
    });

});
</script>
@endsection    
