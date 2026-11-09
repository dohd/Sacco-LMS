@extends('layouts.core')

@section('title', 'Pilot Opening Balance Import')

@section('content')
<div class="pagetitle">
    <h1>Pilot Opening Balance Import</h1>
    <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li><li class="breadcrumb-item active">Pilot Import</li></ol></nav>
</div>

<div class="alert alert-info">
    This flow follows the directive’s Membership sheet. It creates approved member records and opening balances at <strong>31/12/2025</strong> for shares, non-withdrawable deposits, loan principal, and accrued loan interest. Loan disbursement records, repayments, and savings transaction history are not imported in this run. The system posts the opening balances to the selected product ledgers and the selected opening-balance equity account.
</div>

@if ($errors->any())
    <div class="alert alert-danger"><strong>Import needs attention</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@if (session('pilot_import_result'))
    @php($result = session('pilot_import_result'))
    <div class="card"><div class="card-body pt-3">
        <h5 class="card-title">Committed batch</h5>
        <p>Created {{ $result['created_members'] }} member records; matched {{ $result['existing_members'] }} existing members; opened {{ $result['share_accounts'] }} share accounts, {{ $result['savings_accounts'] }} savings accounts, and {{ $result['opening_loans'] }} opening loans.</p>
        @if (!empty($result['membership_numbers']))
            <details><summary>New membership numbers</summary><div class="table-responsive mt-2"><table class="table table-sm table-bordered"><thead><tr><th>Source row</th><th>Member</th><th>Membership number</th><th>KYC</th></tr></thead><tbody>@foreach ($result['membership_numbers'] as $entry)<tr><td>{{ $entry['row'] }}</td><td>{{ $entry['member'] }}</td><td>{{ $entry['membership_number'] }}</td><td>{{ $entry['kyc_status'] }}</td></tr>@endforeach</tbody></table></div></details>
        @endif
    </div></div>
@endif

<div class="card"><div class="card-body pt-3">
    <form method="POST" action="{{ route('pilot_imports.preview') }}" enctype="multipart/form-data">
        @csrf
        <h5 class="card-title">Membership workbook</h5>
        <div class="row g-3">
            <div class="col-md-12"><label class="form-label" for="membership_file">Cleaned Membership workbook</label><input class="form-control" type="file" id="membership_file" name="membership_file" accept=".xls,.xlsx" required><small class="text-muted">Use the directive’s member list columns, including membership_number, KYC fields, status, shares, deposit_balance, loan_balance, and interest_accrued.</small></div>
        </div>
        <h5 class="card-title mt-4">Map opening balances to products</h5>
        <div class="row g-3">
            @foreach (['savings' => ['Savings product', 'savings_product_id'], 'shares' => ['Share product', 'shares_product_id'], 'loans' => ['Loan product', 'loans_product_id']] as $key => $config)
                <div class="col-md-4"><label class="form-label" for="{{ $config[1] }}">{{ $config[0] }}</label><select class="form-select" id="{{ $config[1] }}" name="{{ $config[1] }}" required><option value="">Choose active product</option>@foreach ($products[$key] as $product)<option value="{{ $product->id }}" @if(isset($selected[$config[1]]) && (int)$selected[$config[1]] === (int)$product->id) selected @endif>{{ $product->name }} ({{ $product->code }})</option>@endforeach</select></div>
            @endforeach
            <div class="col-md-4"><label class="form-label" for="opening_equity_account_id">Opening balance equity ledger</label><select class="form-select" id="opening_equity_account_id" name="opening_equity_account_id" required><option value="">Choose active postable equity account</option>@foreach ($products['opening_accounts'] as $account)<option value="{{ $account->id }}" @if(isset($selected['opening_equity_account_id']) && (int)$selected['opening_equity_account_id'] === (int)$account->id) selected @endif>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select><small class="text-muted">Balances the opening subledger postings against the trial balance.</small></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Validate and preview</button></div>
    </form>
</div></div>

@if ($report)
    <div class="card"><div class="card-body pt-3">
        <h5 class="card-title">Preflight summary</h5>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Dataset</th><th>Rows</th><th>Product / ledger</th></tr></thead><tbody>
            <tr><td>Members</td><td>{{ $report['counts']['members'] }} ({{ $report['counts']['new_members'] }} receive system numbers; {{ $report['counts']['kyc_pending'] }} have pending KYC)</td><td>—</td></tr>
            <tr><td>Shares</td><td>{{ $report['counts']['members'] }}</td><td>{{ $report['products']['shares'] }}</td></tr>
            <tr><td>Non-withdrawable deposits</td><td>{{ $report['counts']['members'] }}</td><td>{{ $report['products']['savings'] }}</td></tr>
            <tr><td>Opening loans</td><td>One per member with a loan or accrued-interest balance</td><td>{{ $report['products']['loans'] }}</td></tr>
            <tr><td>Opening balance offset</td><td>—</td><td>{{ $report['products']['opening_equity'] }}</td></tr>
        </tbody></table></div>
        <h6 class="mt-4">Directive balance controls</h6>
        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Balance</th><th>Workbook total</th><th>Expected</th></tr></thead><tbody>
            @foreach (['shares' => 'Shares', 'deposit_balance' => 'Non-withdrawable deposits', 'loan_balance' => 'Loan principal', 'interest_accrued' => 'Interest accrued'] as $key => $label)
                <tr><td>{{ $label }}</td><td>{{ number_format($report['totals'][$key], 2) }}</td><td>{{ number_format(['shares' => 193600, 'deposit_balance' => 7350406.34, 'loan_balance' => 7416754.92, 'interest_accrued' => 840233.79][$key], 2) }}</td></tr>
            @endforeach
        </tbody></table></div>
        <h6 class="mt-4">Status alignment</h6>
        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Directive value</th><th>System value</th><th>Original status</th></tr></thead><tbody>
            @foreach ($report['status_mapping'] as $source => $target)<tr><td>{{ $source }}</td><td>{{ $target }}</td><td>{{ strpos($target, 'remarks') !== false ? 'Retained in remarks' : '—' }}</td></tr>@endforeach
        </tbody></table></div>
        @if (count($report['errors']))
            <div class="alert alert-danger mb-0"><strong>{{ count($report['errors']) }} issue(s) must be fixed before commit.</strong><ul class="mb-0">@foreach ($report['errors'] as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
        @elseif ($batchToken)
            <div class="alert alert-success">Preflight passed. No records have been written yet.</div>
            <form method="POST" action="{{ route('pilot_imports.commit') }}" onsubmit="return confirm('Commit these approved members and opening balances to the live system? This action cannot be undone from this screen.');">
                @csrf
                <input type="hidden" name="batch_token" value="{{ $batchToken }}">
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="confirm_import" value="1" id="confirm_import" required><label class="form-check-label" for="confirm_import">I reviewed the counts and totals and authorize this pilot batch to create approved members and post opening balances.</label></div>
                <button class="btn btn-danger" type="submit"><i class="bi bi-database-check"></i> Commit pilot batch</button>
            </form>
        @endif
    </div></div>
@endif
@endsection
