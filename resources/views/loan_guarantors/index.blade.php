@extends('layouts.core')
@section('title', 'Loan Guarantors')

@section('content')
<div class="pagetitle">
  <div class="row">
    <div class="col-6">
      <h1>Loan Guarantors</h1>
    </div>
    <div class="col-6">
      <a href="{{ route('loan_guarantors.index', ['filter' => 'in_arrears']) }}" class="btn btn-{{ $arrearsOnly ? 'danger' : 'outline-danger' }} float-end ms-1">Loans in arrears</a>
      <a href="{{ route('loan_guarantors.index') }}" class="btn btn-{{ $arrearsOnly ? 'outline-secondary' : 'secondary' }} float-end">All held guarantors</a>
    </div>
  </div>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
      <li class="breadcrumb-item active">Loan Guarantors</li>
    </ol>
  </nav>
</div>
<div class="card">
  <div class="card-body">
    <div class="card-content p-2">
      <div class="table-responsive">
        <table class="table table-borderless datatable">
          <thead>
            <tr>
              <th>GUARANTOR</th>
              <th>MEMBER NO#</th>
              <th>BORROWER</th>
              <th>LOAN NO#</th>
              <th>LOAN STATUS</th>
              <th>OUTSTANDING</th>
              <th>SHARES HELD</th>
              <th>APPLICATION</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($guarantors as $row)
              <tr>
                <td>{{ $row->guarantor_name }}</td>
                <td>{{ $row->member_number }}</td>
                <td>{{ optional(optional($row->application)->member)->full_name }}</td>
                <td>{{ optional($row->loan)->loan_number }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', optional($row->loan)->status)) }}</td>
                <td>{{ numberFormat(optional($row->loan)->total_outstanding_balance) }}</td>
                <td>{{ numberFormat($row->held_amount) }}</td>
                <td><a href="{{ route('loan_applications.show', $row->loan_application_id) }}">{{ optional($row->application)->application_number }}</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
