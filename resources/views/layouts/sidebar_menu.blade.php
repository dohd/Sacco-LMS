<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="{{ route('home') }}">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    {{-- <li class="nav-heading">CRM</li>
    <li class="nav-item">
      <a class="nav-link collapsed" data-bs-target="#whatsapp" data-bs-toggle="collapse" href="#">      
        <i class="bi bi-whatsapp"></i><span>Whatsapp</span><i class="bi bi-chevron-down ms-auto"></i>        
      </a>
      <ul id="whatsapp" class="nav-content" data-bs-parent="#sidebar-nav">
        <li><a href="{{ route('whatsapp.overview') }}"><i class="bi bi-circle"></i><span>Overview</span></a></li> 
        <li><a href="{{ route('whatsapp.customer_rating') }}"><i class="bi bi-circle"></i><span>Customer Rating</span></a></li>        
        <li><a href="{{ route('whatsapp.message_log') }}"><i class="bi bi-circle"></i><span>WhatsApp Log</span></a></li>              
      </ul>
    </li> 

    <li class="nav-heading">Sales</li>
    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('invoices.index') }}">
        <i class="bi bi-clipboard2-pulse"></i><span>Invoices</span>
      </a>
    </li> --}}

    <li class="nav-item">
      <a class="nav-link collapsed" href="{{ route('users.index') }}">
        <i class="bi bi-people"></i><span>Members</span>
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
