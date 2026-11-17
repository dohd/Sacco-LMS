<div class="pagetitle">
  <div class="row">
    <div class="col-6">
      <h1>{{ auth()->user()->hasRoleCode('member') ? 'My Membership Application' : 'Membership Management' }}</h1>
    </div>
    <div class="col-6">
      @if (!auth()->user()->hasRoleCode('member') && userCan('manage-membership'))
        <a href="{{ route('memberships.create') }}" class="btn btn-primary float-end ms-1"><i class="bi bi-plus-circle"></i> Create</a>
      @endif
      @unless(auth()->user()->hasRoleCode('member'))
        <a href="{{ route('memberships.index') }}" class="btn btn-secondary float-end"><i class="bi bi-card-list"></i> List</a>
      @endunless
    </div>
  </div>

  <nav>
    <ol class="breadcrumb">
      @unless(auth()->user()->hasRoleCode('member'))
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active"><a href="{{ route('memberships.index') }}">Membership Management</a></li>
      @else
        <li class="breadcrumb-item active">My Membership Application</li>
      @endunless
    </ol>
  </nav>
</div>
