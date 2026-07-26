<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="{{ route('home') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('memberships.index') }}">
        <i class="bi bi-people"></i><span>Memberships</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('nominations.index') }}">
        <i class="bi bi-people"></i><span>Member Nominations</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('loan_applications.index') }}">
        <i class="bi bi-clipboard2-pulse"></i><span>Loan Applications</span>
      </a>
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
