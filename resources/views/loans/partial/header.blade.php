<div class="pagetitle">
  <div class="row">
    <div class="col-6">
      <h1>Loan Accounts</h1>
    </div>
    <div class="col-6">
      {{-- @can('create-user') --}}
      {{-- @endcan --}}
      <a href="{{ route('loans.index') }}" class="btn btn-secondary float-end"><i class="bi bi-card-list"></i> List</a>
    </div>
  </div>

  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
      <li class="breadcrumb-item active"><a href="{{ route('loans.index') }}">Loan Accounts</a></li>
    </ol>
  </nav>
</div>
