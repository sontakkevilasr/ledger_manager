@extends('layouts.app')
@section('title', 'Agents')
@section('page-title', 'Agents')

@section('content')

{{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <form method="GET" class="d-flex gap-2 flex-grow-1">
        <input type="text" name="search" class="form-control" style="max-width:260px;"
            placeholder="Search name or phone…" value="{{ request('search') }}">
        <select name="status" class="form-select" style="width:140px;">
            <option value="">All Status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button class="btn btn-primary">Search</button>
        @if(request()->anyFilled(['search', 'status']))
        <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </form>

    <a href="{{ route('agents.create') }}" class="btn btn-primary ms-auto">
        <i class="bi bi-person-plus me-1"></i>Add Agent
    </a>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-badge me-2"></i>{{ $agents->total() }} Agent{{ $agents->total() != 1 ? 's' : '' }}</span>
        <span style="font-size:12px;color:#6c757d;">Used in transaction entries as remark/agent</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Agent Name</th>
                    <th>Phone</th>
                    <th class="text-center">Transactions</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($agents as $agent)
            <tr>
                <td class="text-muted" style="font-size:11px;">{{ $agent->id }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#3b5bdb;flex-shrink:0;">
                            {{ strtoupper(substr($agent->name, 0, 2)) }}
                        </div>
                        <span class="fw-500">{{ $agent->name }}</span>
                    </div>
                </td>
                <td style="font-size:13px;">{{ $agent->phone ?: '—' }}</td>
                <td class="text-center">
                    @if($agent->transactions_count > 0)
                        <a href="{{ route('transactions.index') }}?agent_id={{ $agent->id }}"
                           class="badge" style="background:#eff3ff;color:#3b5bdb;text-decoration:none;font-size:12px;">
                            {{ $agent->transactions_count }}
                        </a>
                    @else
                        <span class="text-muted">0</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge {{ $agent->is_active ? 'badge-active' : 'badge-inactive' }}">
                        {{ $agent->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <a href="{{ route('agents.edit', $agent) }}"
                           class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('agents.toggle', $agent) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm {{ $agent->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                title="{{ $agent->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi {{ $agent->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('agents.destroy', $agent) }}"
                            onsubmit="return confirm('Delete agent {{ $agent->name }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-person-badge display-6 d-block mb-2 opacity-25"></i>
                    No agents found.
                    <a href="{{ route('agents.create') }}">Add your first agent</a>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($agents->hasPages())
    <div class="card-footer bg-white">{{ $agents->links() }}</div>
    @endif
</div>

@endsection
