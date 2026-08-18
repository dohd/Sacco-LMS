<div class="pagetitle">
  <div class="row">
    <div class="col-6">
      <h1>Savings Transactions</h1>
    </div>
    <div class="col-6">
      {{-- @can('create-user') --}}
        <a href="{{ route('savings_transactions.create') }}" class="btn btn-primary float-end ms-1"><i class="bi bi-plus-circle"></i> Create</a>
      {{-- @endcan --}}
      <a href="{{ route('savings_transactions.index') }}" class="btn btn-secondary float-end"><i class="bi bi-card-list"></i> List</a>
    </div>
  </div>

  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
      <li class="breadcrumb-item active"><a href="{{ route('savings_transactions.index') }}">Savings Transactions</a></li>
    </ol>
  </nav>
</div>