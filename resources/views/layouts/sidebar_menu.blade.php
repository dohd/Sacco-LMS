<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="{{ route('home') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    @if(userCan(['view-members', 'view-nominations']))
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#members" data-bs-toggle="collapse" href="#">
        <i class="bi bi-people"></i><span>Members</span><i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="members" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('memberships.index') }}"><i class="bi bi-circle"></i><span>Member List</span></a></li>
        <li><a href="{{ route('nominations.index') }}"><i class="bi bi-circle"></i><span>Member Nominations</span></a></li>
      </ul>
    </li>
    @endif    

    @if(userCan(['view-members', 'approve-loan', 'view-own-loan']))
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#loans" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-credit-card"></i><span>Loans</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="loans" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        @if (auth()->user()->member)
          <li><a href="{{ route('loan_applications.index') }}"><i class="bi bi-circle"></i><span>Loan Applications</span></a></li>
          <li><a href="{{ route('loans.index') }}"><i class="bi bi-circle"></i><span>Loan Accounts</span></a></li>
          <hr>
          <li><a href="{{ route('loan_products.index') }}"><i class="bi bi-circle"></i><span>Loan Products</span></a></li>
          <li><a href="{{ route('loan_disbursements.index') }}"><i class="bi bi-circle"></i><span>Loan Disbursements</span></a></li>
          <li><a href="{{ route('loan_repayment_schedules.index') }}"><i class="bi bi-circle"></i><span>Loan Repayment Schedules</span></a></li>
          <li><a href="{{ route('loan_repayments.index') }}"><i class="bi bi-circle"></i><span>Loan Repayments</span></a></li>
        @else
          <li><a href="{{ route('loan_products.index') }}"><i class="bi bi-circle"></i><span>Loan Products</span></a></li>
          <li><a href="{{ route('loan_applications.index') }}"><i class="bi bi-circle"></i><span>Loan Applications</span></a></li>
          <li><a href="{{ route('loans.index') }}"><i class="bi bi-circle"></i><span>Approved Loans</span></a></li>
          @if(userCan('manage-guarantors'))
            <li><a href="{{ route('loan_guarantees.index') }}"><i class="bi bi-circle"></i><span>Loan Guarantees</span></a></li>
          @endif
          <li><a href="{{ route('loan_disbursements.index') }}"><i class="bi bi-circle"></i><span>Loan Disbursements</span></a></li>
          <li><a href="{{ route('loan_repayment_schedules.index') }}"><i class="bi bi-circle"></i><span>Loan Repayment Schedules</span></a></li>
          <li><a href="{{ route('loan_repayments.index') }}"><i class="bi bi-circle"></i><span>Loan Repayments</span></a></li>
        @endif
      </ul>
    </li>      
    @endif

    @if(userCan(['view-own-savings', 'manage-savings-products', 'confirm-savings-transaction']))
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#savings" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-piggy-bank"></i><span>Savings</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="savings" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        @if (auth()->user()->member)
          <li><a href="{{ route('savings_transactions.index') }}"><i class="bi bi-circle"></i><span>Savings Transactions</span></a></li>
          <li><a href="{{ route('savings_withdrawals.index') }}"><i class="bi bi-circle"></i><span>Savings Withdrawals</span></a></li>
          <hr>
          <li><a href="{{ route('savings_products.index') }}"><i class="bi bi-circle"></i><span>Savings Products</span></a></li>
          <li><a href="{{ route('savings_accounts.index') }}"><i class="bi bi-circle"></i><span>Savings Accounts</span></a></li>
        @else
          <li><a href="{{ route('savings_products.index') }}"><i class="bi bi-circle"></i><span>Savings Products</span></a></li>
          <li><a href="{{ route('savings_accounts.index') }}"><i class="bi bi-circle"></i><span>Savings Accounts</span></a></li>
          <li><a href="{{ route('savings_transactions.index') }}"><i class="bi bi-circle"></i><span>Savings Transactions</span></a></li>
          <li><a href="{{ route('savings_withdrawals.index') }}"><i class="bi bi-circle"></i><span>Savings Withdrawals</span></a></li>
        @endif
      </ul>
    </li>
    @endif

    @if(userCan(['manage-accounting-periods', 'manage-chart-of-accounts']))
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#accounting" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-journal-bookmark"></i><span>Accounting</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="accounting" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('accounting_periods.index') }}"><i class="bi bi-circle"></i><span>Accounting Periods</span></a></li>
        <li><a href="{{ route('chart_of_accounts.index') }}"><i class="bi bi-circle"></i><span>Charts Of Accounts</span></a></li>
        <li><a href="{{ route('journal_entries.index') }}"><i class="bi bi-circle"></i><span>Journal Entries</span></a></li>
      </ul>
    </li>
    @endif

    @if(userCan(['view-own-shares', 'manage-share-products', 'confirm-share-transaction']))
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#shares" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-graph-up"></i><span>Shares & Dividends</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="shares" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('share_products.index') }}"><i class="bi bi-circle"></i><span>Share Products</span></a></li>
        <li><a href="{{ route('share_accounts.index') }}"><i class="bi bi-circle"></i><span>Share Accounts</span></a></li>
        {{-- <li><a href="#"><i class="bi bi-circle"></i><span>Share Hold</span></a></li> --}}
        <li><a href="{{ route('share_transactions.index') }}"><i class="bi bi-circle"></i><span>Share Transactions</span></a></li>
        {{-- <li><a href="#"><i class="bi bi-circle"></i>Dividend Runs<span class="text-danger">*</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i>Dividend Allocations<span class="text-danger">*</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i>Dividend Deductions<span class="text-danger">*</span></a></li> --}}
      </ul>
    </li>
    @endif

    <li class="nav-heading">Reports</li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#financialReports" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-journal-bookmark"></i><span>Financial Reports</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="financialReports" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="#"><i class="bi bi-circle"></i><span>Trial Balance</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Daily Transaction Log</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Balance Sheet</span></a></li>
      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#memberReports" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-journal-bookmark"></i><span>Member Reports</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="memberReports" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="#"><i class="bi bi-circle"></i><span>Member Statement</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Member Listing</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Product-Wise Savings Summary</span></a></li>
      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#loanReports" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-journal-bookmark"></i><span>Loan Portfolio Reports</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="loanReports" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="#"><i class="bi bi-circle"></i><span>Loan Disbursal Report</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Loan Aging</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Repayment Schedule vs. Actual</span></a></li>
      </ul>
    </li>


    <li class="nav-heading">Settings</li>
    <!-- user management -->
    @if(userCan('manage-users'))
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('users.index') }}">
        <i class="bi bi-people"></i><span>Users</span>
      </a>
    </li>
    @endif
    @if(userCan('view-members'))
    <li class="nav-item"><a class="nav-link collapsed" href="{{ route('pilot_imports.index') }}"><i class="bi bi-upload"></i><span>Members Data Import</span></a></li>
    @endif

    <li class="nav-item">
      <a class="nav-link collapsed" href="#">
        {{-- <i class="bi bi-gear-wide-connected"></i>General<span class="text-danger">*</span> --}}
      </a>
    </li>
  </ul>
</aside>
