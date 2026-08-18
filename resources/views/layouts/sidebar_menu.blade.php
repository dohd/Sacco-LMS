<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="{{ route('home') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#members" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-people"></i><span>Members</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="members" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('memberships.index') }}"><i class="bi bi-circle"></i><span>Member List</span></a></li>
        <li><a href="{{ route('nominations.index') }}"><i class="bi bi-circle"></i><span>Member Nominations</span></a></li>
      </ul>
    </li>  

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#loans" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-credit-card"></i><span>Loans</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="loans" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('loan_products.index') }}"><i class="bi bi-circle"></i><span>Loan Products</span></a></li>
        <li><a href="{{ route('loan_applications.index') }}"><i class="bi bi-circle"></i><span>Loan Applications</span></a></li>
        <li><a href="{{ route('loan_disbursements.index') }}"><i class="bi bi-circle"></i><span>Loan Disbursements</span></a></li>
        <li><a href="{{ route('loan_repayments.index') }}"><i class="bi bi-circle"></i><span>Loan Repayments</span></a></li>
      </ul>
    </li>   

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#savings" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-piggy-bank"></i><span>Savings</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="savings" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('savings_products.index') }}"><i class="bi bi-circle"></i><span>Savings Products</span></a></li>
        <li><a href="{{ route('savings_accounts.index') }}"><i class="bi bi-circle"></i><span>Savings Accounts</span></a></li>
        <li><a href="{{ route('savings_transactions.index') }}"><i class="bi bi-circle"></i><span>Savings Transactions</span></a></li>
        <li><a href="{{ route('savings_withdrawals.index') }}"><i class="bi bi-circle"></i><span>Savings Withdrawals</span></a></li>
      </ul>
    </li>   

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#accounting" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-journal-bookmark"></i><span>Accounting</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="accounting" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="#"><i class="bi bi-circle"></i><span>Charts Of Accounts</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Accounting Periods</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Journal Entries</span></a></li>
      </ul>
    </li> 

    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#shares" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-graph-up"></i><span>Shares & Dividends</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="shares" class="nav-content collapse" data-bs-parent="#sidebar-nav">
        <li><a href="#"><i class="bi bi-circle"></i><span>Share Products</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Share Accounts</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Share Hold</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Share Transactions</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Dividend Runs</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Dividend Allocations</span></a></li>
        <li><a href="#"><i class="bi bi-circle"></i><span>Dividend Deductions</span></a></li>
      </ul>
    </li>     

    <li class="nav-heading">Settings</li>
    <!-- user management -->
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('users.index') }}">
        <i class="bi bi-people"></i><span>Users</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('settings.create') }}">
        <i class="bi bi-gear-wide-connected"></i><span>General</span>
      </a>
    </li>
  </ul>
</aside>
