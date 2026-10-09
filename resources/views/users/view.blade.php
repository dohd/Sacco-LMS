@extends('layouts.core')
@section('title', 'View | User Profile')
    
@section('content')
    @include('users.partial.header')

    @php
        $statusClass = $user->is_active ? 'success' : 'danger';
        $statusText = $user->is_active ? 'Active' : 'Inactive';
    @endphp

    <div class="container-fluid">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">
                    {{ $user->name }}
                </h4>
                <div class="text-muted">
                    User Account
                    @if($user->employee_number)
                        · {{ $user->employee_number }}
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">                
                @if($user->is_active)
                    <a href="{{ route('users.edit', $user->id) }}"
                       class="btn btn-primary">
                        <i class="fa fa-edit"></i>
                        Edit
                    </a>
                    <button type="button"
                            class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deactivateModal">
                        <i class="fa fa-user-times"></i>
                        Deactivate
                    </button>
                @else
                    <form method="POST"
                          action="{{ route('users.activate', $user->id) }}"
                          class="d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-success">
                            <i class="fa fa-user-check"></i>
                            Activate
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Status --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="rounded-circle bg-light
                                            d-flex align-items-center
                                            justify-content-center"
                                     style="width:55px;height:55px;">
                                    <i class="fa fa-user fa-lg text-secondary"></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    {{ $user->name }}
                                </h5>
                                <div class="text-muted">
                                    {{ $user->email }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-{{ $statusClass }} fs-6 px-3 py-2">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Account Status
                        </div>
                        <h5 class="mb-0 text-{{ $statusClass }}">
                            {{ $statusText }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Employee Number
                        </div>
                        <h5 class="mb-0">
                            {{ $user->employee_number ?: '—' }}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            Last Login
                        </div>
                        <h6 class="mb-0">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d M Y') }}
                                <div class="text-muted small">
                                    {{ $user->last_login_at->format('H:i') }}
                                </div>
                            @else
                                Never logged in
                            @endif
                        </h6>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">
                            User ID
                        </div>
                        <h5 class="mb-0">
                            #{{ $user->id }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- Personal / Contact Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    User Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Full Name
                        </div>
                        <div class="fw-semibold">
                            {{ $user->name }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Employee Number
                        </div>
                        <div>
                            {{ $user->employee_number ?: '—' }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Email Address
                        </div>
                        <div>
                            <a href="mailto:{{ $user->email }}">
                                {{ $user->email }}
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="text-muted small">
                            Phone Number
                        </div>
                        <div>
                            @if($user->phone)
                                <a href="tel:{{ $user->phone }}">
                                    {{ $user->phone }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">
                            Account Status
                        </div>
                        <span class="badge bg-{{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Security Information --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Security & Login
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Last Login
                        </div>
                        <div>
                            {{ $user->last_login_at
                                ? $user->last_login_at->format('d M Y H:i')
                                : 'Never logged in' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Account Created
                        </div>
                        <div>
                            {{ optional($user->created_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="text-muted small">
                            Last Updated
                        </div>
                        <div>
                            {{ optional($user->updated_at)->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Roles --}}
        @if(method_exists($user, 'roles'))
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">
                        Roles & Access
                    </h6>
                    <a href="{{ route('users.edit', $user->id) }}"
                       class="btn btn-sm btn-outline-primary">
                        Manage Access
                    </a>
                </div>
                <div class="card-body">
                    @if($user->roles->count())
                        @foreach($user->roles as $role)
                            <span class="badge bg-primary me-1 mb-1">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-muted">
                            No roles assigned.
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Audit --}}
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    Account Audit
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted small">
                            User ID
                        </div>
                        <div>
                            #{{ $user->id }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Created
                        </div>
                        <div>
                            {{ optional($user->created_at)
                                ->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">
                            Updated
                        </div>
                        <div>
                            {{ optional($user->updated_at)
                                ->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Deactivate Modal --}}
    @if($user->is_active)
        <div class="modal fade"
             id="deactivateModal"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('users.deactivate', $user->id) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Deactivate User
                            </h5>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                You are about to deactivate
                                <strong>{{ $user->name }}</strong>.
                            </div>
                            <p class="mb-0">
                                The user will no longer be able to access
                                the system.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-light"
                                    data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="btn btn-danger">
                                Deactivate User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
