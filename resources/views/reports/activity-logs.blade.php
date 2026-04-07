@extends('layouts.app')
@section('title','Activity Logs')
@section('page-title','Activity Logs')

@section('content')

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Module</label>
                <select name="module" class="form-select form-select-sm" style="width:140px;">
                    <option value="">All Modules</option>
                    @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module')==$m?'selected':'' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">Action</label>
                <select name="action" class="form-select form-select-sm" style="width:130px;">
                    <option value="">All Actions</option>
                    @foreach(['created','updated','deleted','viewed','exported','login','logout'] as $a)
                    <option value="{{ $a }}" {{ request('action')==$a?'selected':'' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">User</label>
                <select name="user_id" class="form-select form-select-sm" style="width:150px;">
                    <option value="">All Users</option>
                    @foreach($users as $id=>$name)
                    <option value="{{ $id }}" {{ request('user_id')==$id?'selected':'' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">From</label>
                <input type="date" name="from" class="form-control form-control-sm" style="width:140px;" value="{{ request('from') }}">
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;">To</label>
                <input type="date" name="to" class="form-control form-control-sm" style="width:140px;" value="{{ request('to') }}">
            </div>
            <div class="d-flex gap-1 align-self-end">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('reports.activity-logs') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2"></i>{{ $logs->total() }} Log Entries</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Record</th>
                    <th>Description</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @php
            $actionColors = [
                'created'  => 'success', 'updated' => 'primary', 'deleted' => 'danger',
                'viewed'   => 'secondary','exported'=> 'info',   'login'   => 'success',
                'logout'   => 'warning', 'failed_login' => 'danger',
            ];
            @endphp
            @forelse($logs as $log)
            <tr>
                <td style="white-space:nowrap;font-size:11px;color:#6c757d;">
                    {{ $log->created_at->format('d M y') }}<br>
                    {{ $log->created_at->format('H:i:s') }}
                </td>
                <td style="font-size:12px;">{{ $log->user?->name ?? '<em class="text-muted">System</em>' }}</td>
                <td style="font-size:11px;color:#6c757d;">{{ $log->role_name ?? '—' }}</td>
                <td>
                    @php $col = $actionColors[$log->action] ?? 'secondary'; @endphp
                    <span class="badge bg-{{ $col }}-subtle text-{{ $col }} border border-{{ $col }}-subtle" style="font-size:10px;">
                        {{ $log->action }}
                    </span>
                </td>
                <td style="font-size:12px;">{{ $log->module }}</td>
                <td style="font-size:12px;">{{ $log->record_label ?? '—' }}</td>
                <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $log->description }}">
                    {{ $log->description }}
                </td>
                <td style="font-size:11px;color:#6c757d;">{{ $log->ip_address }}</td>
                <td>
                    @if($log->old_values || $log->new_values)
                    <button class="btn btn-sm btn-link p-0 text-muted" data-bs-toggle="modal"
                        data-bs-target="#diffModal"
                        data-old="{{ json_encode($log->old_values) }}"
                        data-new="{{ json_encode($log->new_values) }}"
                        data-label="{{ $log->record_label }}">
                        <i class="bi bi-eye"></i>
                    </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center py-5 text-muted">No logs found</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white">{{ $logs->links() }}</div>
    @endif
</div>

{{-- Diff Modal --}}
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-6">Change Details — <span id="diff-label"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div style="font-size:12px;font-weight:600;color:#dc2626;margin-bottom:6px;">Before</div>
                        <pre id="diff-old" style="background:#fff5f5;border:1px solid #fee2e2;border-radius:8px;padding:12px;font-size:12px;overflow:auto;max-height:300px;"></pre>
                    </div>
                    <div class="col-6">
                        <div style="font-size:12px;font-weight:600;color:#059669;margin-bottom:6px;">After</div>
                        <pre id="diff-new" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;font-size:12px;overflow:auto;max-height:300px;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('diffModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('diff-label').textContent = btn.dataset.label || '';
    document.getElementById('diff-old').textContent = JSON.stringify(JSON.parse(btn.dataset.old || 'null'), null, 2);
    document.getElementById('diff-new').textContent = JSON.stringify(JSON.parse(btn.dataset.new || 'null'), null, 2);
});
</script>
@endpush
