<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Aman Traders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width:240px; --sidebar-bg:#1a1f2e; --brand:#3b5bdb; }
        body { background:#f4f6fb; font-family:'Segoe UI',sans-serif; font-size:14px; }

        #sidebar {
            position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-width);
            background:var(--sidebar-bg); z-index:1000; overflow-y:auto;
            display:flex; flex-direction:column;
        }
        .sb-brand { padding:18px 20px 14px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sb-brand .b-name { color:#fff; font-size:16px; font-weight:600; display:block; }
        .sb-brand .b-sub  { color:rgba(255,255,255,.4); font-size:11px; }
        .sb-section { padding:16px 12px 4px; font-size:10px; font-weight:600; color:rgba(255,255,255,.3); letter-spacing:1px; text-transform:uppercase; }
        .sb-nav .nav-link {
            display:flex; align-items:center; gap:10px;
            padding:9px 16px; margin:1px 8px; color:rgba(255,255,255,.65);
            border-radius:7px; text-decoration:none; font-size:13px; transition:all .15s;
        }
        .sb-nav .nav-link:hover  { background:rgba(255,255,255,.07); color:#fff; }
        .sb-nav .nav-link.active { background:var(--brand); color:#fff; }
        .sb-nav .nav-link i      { font-size:15px; width:18px; text-align:center; }
        .sb-footer {
            padding:14px 16px; border-top:1px solid rgba(255,255,255,.08);
            color:rgba(255,255,255,.4); font-size:11px; margin-top:auto;
        }
        .sb-footer strong { color:rgba(255,255,255,.75); display:block; }

        #main { margin-left:var(--sidebar-width); min-height:100vh; }
        #topbar {
            height:56px; background:#fff; border-bottom:1px solid #e8ecf0;
            display:flex; align-items:center; padding:0 24px; gap:16px;
            position:sticky; top:0; z-index:100;
        }
        .topbar-title { font-size:16px; font-weight:600; color:#1a1f2e; }
        .topbar-right  { margin-left:auto; display:flex; align-items:center; gap:12px; }
        .user-pill {
            display:flex; align-items:center; gap:8px; background:#f4f6fb;
            border-radius:20px; padding:5px 12px 5px 5px; cursor:pointer;
            border:1px solid #e8ecf0;
        }
        .user-avatar {
            width:28px; height:28px; border-radius:50%; background:var(--brand);
            color:#fff; font-size:11px; font-weight:600;
            display:flex; align-items:center; justify-content:center;
        }

        .page-content { padding:24px; }
        .card { border:1px solid #e8ecf0; border-radius:10px; box-shadow:none; }
        .card-header { background:#fff; border-bottom:1px solid #e8ecf0; padding:14px 20px; font-weight:600; }

        .stat-card { border-radius:10px; padding:20px; border:1px solid #e8ecf0; background:#fff; }
        .stat-label { font-size:12px; color:#6c757d; margin-bottom:6px; }
        .stat-value { font-size:22px; font-weight:700; }
        .stat-icon  { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; }

        .table th { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; color:#6c757d; border-bottom:2px solid #e8ecf0; background:#f9fafb; }
        .table td { vertical-align:middle; font-size:13px; }

        .badge-credit   { background:#d1fae5; color:#065f46; font-weight:500; }
        .badge-debit    { background:#fee2e2; color:#991b1b; font-weight:500; }
        .badge-active   { background:#dbeafe; color:#1e40af; }
        .badge-inactive { background:#f3f4f6; color:#6b7280; }

        .bal-pos { color:#059669; font-weight:600; }
        .bal-neg { color:#dc2626; font-weight:600; }
        .bal-zero{ color:#6b7280; }

        .btn-primary { background:var(--brand); border-color:var(--brand); }
        .btn-primary:hover { background:#2f4bbd; border-color:#2f4bbd; }

        .flash-wrap { position:fixed; top:66px; right:20px; z-index:9999; min-width:300px; max-width:400px; }

        @media(max-width:768px){ #sidebar{ transform:translateX(-100%); } #sidebar.open{ transform:translateX(0); } #main{ margin-left:0; } }
    </style>
    @stack('styles')
</head>
<body>

<nav id="sidebar">
    <div class="sb-brand">
        <span class="b-name"><i class="bi bi-shop me-2"></i>Aman Traders</span>
        <span class="b-sub">Ledger Management System</span>
    </div>

    <div class="sb-nav mt-1">
        <div class="sb-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        @if(auth()->user()->hasPermission('customers.view') || auth()->user()->isSuperAdmin())
        <div class="sb-section">Customers</div>
        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.index','customers.show') ? 'active' : '' }}">
            <i class="bi bi-people"></i> All Customers
        </a>
        @if(auth()->user()->hasPermission('customers.create') || auth()->user()->isSuperAdmin())
        <a href="{{ route('customers.create') }}" class="nav-link {{ request()->routeIs('customers.create') ? 'active' : '' }}">
            <i class="bi bi-person-plus"></i> Add Customer
        </a>
        @endif
        @endif

        @if(auth()->user()->hasPermission('transactions.view') || auth()->user()->isSuperAdmin())
        <div class="sb-section">Transactions</div>
        <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i> All Transactions
        </a>
        @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
        <a href="{{ route('transactions.create') }}" class="nav-link {{ request()->routeIs('transactions.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle"></i> Add Entry
        </a>
        @endif
        @endif

        @if(auth()->user()->hasPermission('reports.view') || auth()->user()->isSuperAdmin())
        <div class="sb-section">Reports</div>
        <a href="{{ route('reports.balance-summary') }}"  class="nav-link {{ request()->routeIs('reports.balance-summary') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i> Balance Summary</a>
        <a href="{{ route('reports.date-range') }}"       class="nav-link {{ request()->routeIs('reports.date-range') ? 'active' : '' }}"><i class="bi bi-calendar-range"></i> Date Range</a>
        <a href="{{ route('reports.customer-ledger') }}"  class="nav-link {{ request()->routeIs('reports.customer-ledger') ? 'active' : '' }}"><i class="bi bi-journal-text"></i> Customer Ledger</a>
        <a href="{{ route('reports.city-wise') }}"        class="nav-link {{ request()->routeIs('reports.city-wise') ? 'active' : '' }}"><i class="bi bi-geo-alt"></i> City Wise</a>
        <a href="{{ route('reports.agent-wise') }}"       class="nav-link {{ request()->routeIs('reports.agent-wise') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> Agent Wise</a>
        @endif

        @if(auth()->user()->hasPermission('masters.manage') || auth()->user()->isSuperAdmin())
        <div class="sb-section">Masters</div>
        <a href="{{ route('agents.index') }}" class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Agents
        </a>
        @endif

        @if(auth()->user()->hasPermission('logs.view') || auth()->user()->isSuperAdmin())
        <div class="sb-section">System</div>
        <a href="{{ route('reports.activity-logs') }}" class="nav-link {{ request()->routeIs('reports.activity-logs') ? 'active' : '' }}">
            <i class="bi bi-clock-history"></i> Activity Logs
        </a>
        @endif

        <!-- @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-shield-lock"></i> Users & Roles
        </a>
        @endif -->
    </div>

    <div class="sb-footer">
        Logged in as<br>
        <strong>{{ auth()->user()->name }}</strong>
        <span style="font-size:10px;color:rgba(255,255,255,.3);">{{ auth()->user()->role_name }}</span>
    </div>
</nav>

<div id="main">
    <div id="topbar">
        <button class="btn btn-sm d-md-none border-0 p-1" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="topbar-title">@yield('page-title','Dashboard')</span>
        <div class="topbar-right">
            @if(auth()->user()->hasPermission('transactions.create') || auth()->user()->isSuperAdmin())
            <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Add Entry
            </a>
            @endif
            <div class="dropdown">
                <div class="user-pill" data-bs-toggle="dropdown">
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
                    <span style="font-size:13px;">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down" style="font-size:10px;color:#6c757d;"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px;min-width:180px;">
                    <li><span class="dropdown-item-text text-muted" style="font-size:11px;">{{ auth()->user()->role_name }}</span></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="flash-wrap">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm d-flex gap-2 align-items-start">
            <i class="bi bi-check-circle-fill mt-1"></i><div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible shadow-sm d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-circle-fill mt-1"></i><div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <div class="page-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    setTimeout(()=>{ document.querySelectorAll('.flash-wrap .alert').forEach(a=>new bootstrap.Alert(a).close()); },4500);
});
function inr(n){ return '₹'+Number(n).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
</script>
@stack('scripts')
</body>
</html>
