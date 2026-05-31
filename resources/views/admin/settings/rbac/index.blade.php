@extends('layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<style>
    .rbac-page { max-width: 1280px; margin: 0 auto; }
    .rbac-hero {
        background: linear-gradient(135deg, #0b1220 0%, #1e293b 100%);
        border-radius: 16px; color: #fff; padding: 1.35rem 1.5rem; margin-bottom: 1.25rem;
        box-shadow: 0 12px 40px rgba(15, 23, 42, .15);
    }
    .rbac-hero h1 { font-size: 1.35rem; font-weight: 800; margin: 0 0 .35rem; letter-spacing: -.02em; }
    .rbac-hero p { margin: 0; color: rgba(255,255,255,.75); font-size: .9rem; }
    .rbac-layout {
        display: grid; grid-template-columns: 280px 1fr; gap: 1.25rem; align-items: start;
    }
    @media (max-width: 991px) {
        .rbac-layout { grid-template-columns: 1fr; }
    }
    .rbac-roles-panel {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
        overflow: hidden; box-shadow: 0 4px 24px rgba(15,23,42,.06);
        position: sticky; top: 1rem;
    }
    .rbac-roles-head {
        padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9;
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .08em; color: #64748b; background: #fafbfc;
    }
    .rbac-role-item {
        display: block; padding: .85rem 1rem; text-decoration: none; color: inherit;
        border-bottom: 1px solid #f1f5f9; transition: background .12s;
    }
    .rbac-role-item:hover { background: #f8fafc; }
    .rbac-role-item.active {
        background: linear-gradient(90deg, #eff6ff, #fff);
        border-left: 3px solid #2563eb; padding-left: calc(1rem - 3px);
    }
    .rbac-role-name { font-weight: 700; font-size: .9rem; color: #0f172a; }
    .rbac-role-slug { font-size: .7rem; color: #94a3b8; font-family: ui-monospace, monospace; }
    .rbac-role-meta { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .4rem; }
    .rbac-role-badge {
        font-size: .65rem; font-weight: 700; padding: .15rem .45rem; border-radius: 999px;
        background: #f1f5f9; color: #475569;
    }
    .rbac-role-badge.on { background: #dbeafe; color: #1d4ed8; }
    .rbac-editor {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
        box-shadow: 0 8px 30px rgba(15,23,42,.06); overflow: hidden;
    }
    .rbac-editor-head {
        padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;
        background: linear-gradient(180deg, #fafbfc, #fff);
    }
    .rbac-editor-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0; }
    .rbac-toolbar {
        display: flex; flex-wrap: wrap; gap: .5rem; align-items: center;
        padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9; background: #fafbfc;
    }
    .rbac-search {
        flex: 1; min-width: 200px; max-width: 320px;
    }
    .rbac-search .form-control { border-radius: 999px; font-size: .875rem; }
    .rbac-progress {
        font-size: .8rem; color: #64748b; font-weight: 600;
    }
    .rbac-progress strong { color: #0f172a; }
    .rbac-groups { padding: .5rem 0 1rem; }
    .rbac-group {
        border-bottom: 1px solid #f1f5f9;
    }
    .rbac-group:last-child { border-bottom: 0; }
    .rbac-group-header {
        display: flex; align-items: center; gap: .75rem;
        padding: .85rem 1.25rem; cursor: pointer; user-select: none;
    }
    .rbac-group-header:hover { background: #f8fafc; }
    .rbac-group-icon {
        width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eff6ff; color: #2563eb; font-size: 1.1rem;
    }
    .rbac-group-icon.staff { background: #f5f3ff; color: #6d28d9; }
    .rbac-group-icon.analytics { background: #ecfdf5; color: #059669; }
    .rbac-group-icon.rbac { background: #fef2f2; color: #dc2626; }
    .rbac-group-icon.platform { background: #fff7ed; color: #ea580c; }
    .rbac-group-icon.settings { background: #f1f5f9; color: #475569; }
    .rbac-group-label { font-weight: 700; font-size: .9rem; color: #0f172a; }
    .rbac-group-desc { font-size: .75rem; color: #64748b; margin-top: .1rem; }
    .rbac-group-actions { margin-left: auto; display: flex; gap: .35rem; align-items: center; }
    .rbac-group-count {
        font-size: .7rem; font-weight: 700; padding: .2rem .5rem; border-radius: 999px;
        background: #e2e8f0; color: #475569;
    }
    .rbac-group-body { padding: 0 1.25rem 1rem 4.5rem; }
    .rbac-perm-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: .5rem;
    }
    .rbac-perm {
        display: flex; align-items: flex-start; gap: .65rem; padding: .65rem .75rem;
        border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer;
        transition: border-color .12s, background .12s, box-shadow .12s;
    }
    .rbac-perm:hover { border-color: #93c5fd; background: #f8fafc; }
    .rbac-perm:has(input:checked) {
        border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 1px rgba(59,130,246,.2);
    }
    .rbac-perm.hidden-by-search { display: none !important; }
    .rbac-perm input { margin-top: .2rem; flex-shrink: 0; }
    .rbac-perm-name { font-weight: 600; font-size: .82rem; color: #0f172a; line-height: 1.3; }
    .rbac-perm-slug { font-size: .68rem; color: #94a3b8; font-family: ui-monospace, monospace; }
    .rbac-perm-desc { font-size: .72rem; color: #64748b; margin-top: .15rem; }
    .rbac-footer {
        position: sticky; bottom: 0; padding: 1rem 1.25rem;
        border-top: 1px solid #e2e8f0; background: rgba(255,255,255,.95);
        backdrop-filter: blur(8px);
        display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between;
    }
    .rbac-locked {
        text-align: center; padding: 3rem 2rem; color: #64748b;
    }
    .rbac-locked-icon {
        width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 16px;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309;
        display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
    }
    .rbac-hint-bar {
        background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
        padding: .75rem 1rem; font-size: .85rem; color: #92400e; margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
    @php
        $selected = $selectedRole;
        $selectedPermIds = $selected?->permissions->pluck('id')->all() ?? [];
        $enabledCount = count($selectedPermIds);
        $sortedGroups = $permissionsByGroup->sortBy(fn ($_, $key) => $permissionGroups[$key]['order'] ?? 99);
    @endphp

    <div class="rbac-page">
        <div class="rbac-hero">
            <h1><i class="bi bi-shield-check me-2"></i>Roles &amp; permissions</h1>
            <p>Control what each CRM role can do — similar to permission sets in HubSpot or profiles in Salesforce.</p>
        </div>

        <div class="rbac-hint-bar">
            <i class="bi bi-lightbulb me-1"></i>
            Changes apply to all staff with that role. Use <strong>Talent</strong> vs <strong>Company</strong> team on
            <a href="{{ route('admin.staff.index') }}" class="alert-link">Staff logins</a> to control which pipeline they see.
        </div>

        <div class="rbac-layout">
            <nav class="rbac-roles-panel" aria-label="CRM roles">
                <div class="rbac-roles-head">CRM roles</div>
                @foreach($roles as $role)
                    @php
                        $count = (int) ($adminCountsByRole[$role->id] ?? 0);
                        $permCount = $role->permissions->count();
                    @endphp
                    <a href="{{ route('admin.settings.rbac', ['role' => $role->slug]) }}"
                       class="rbac-role-item @if($selected && $selected->id === $role->id) active @endif">
                        <div class="rbac-role-name">{{ $role->name }}</div>
                        <div class="rbac-role-slug">{{ $role->slug }}</div>
                        <div class="rbac-role-meta">
                            <span class="rbac-role-badge @if($permCount > 0) on @endif">{{ $permCount }} permissions</span>
                            @if($count > 0)
                                <span class="rbac-role-badge">{{ $count }} {{ $count === 1 ? 'user' : 'users' }}</span>
                            @endif
                            @if($role->is_system)
                                <span class="rbac-role-badge">System</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </nav>

            <div class="rbac-editor">
                @if($selected)
                    <div class="rbac-editor-head">
                        <div>
                            <h2 class="rbac-editor-title">{{ $selected->name }}</h2>
                            <code class="small text-muted">{{ $selected->slug }}</code>
                            @if($selected->is_system)
                                <span class="badge text-bg-secondary ms-1">System role</span>
                            @endif
                        </div>
                        <div class="rbac-progress">
                            <strong id="rbac-enabled-count">{{ $enabledCount }}</strong>
                            / {{ $totalPermissions }} enabled
                        </div>
                    </div>

                    @if($selected->slug === 'super_admin')
                        <div class="rbac-locked">
                            <div class="rbac-locked-icon"><i class="bi bi-lock-fill"></i></div>
                            <h3 class="h5 text-dark">Super Admin is locked</h3>
                            <p class="mb-0 small">This role always has full access and cannot be restricted.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.settings.rbac.update', $selected) }}" id="rbac-form">
                            @csrf
                            @method('PUT')

                            <div class="rbac-toolbar">
                                <div class="rbac-search input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="search" class="form-control border-start-0" id="rbac-search"
                                           placeholder="Filter permissions…" autocomplete="off">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="rbac-enable-all">Enable all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="rbac-disable-all">Disable all</button>
                            </div>

                            <div class="rbac-groups">
                                @foreach($sortedGroups as $group => $permissions)
                                    @php
                                        $meta = $permissionGroups[$group] ?? ['label' => ucfirst($group), 'icon' => 'bi-grid', 'description' => ''];
                                        $groupEnabled = $permissions->filter(fn ($p) => in_array($p->id, $selectedPermIds))->count();
                                    @endphp
                                    <div class="rbac-group" data-group="{{ $group }}">
                                        <div class="rbac-group-header" data-bs-toggle="collapse"
                                             data-bs-target="#rbac-group-{{ $group }}" aria-expanded="true">
                                            <div class="rbac-group-icon {{ $group }}">
                                                <i class="bi {{ $meta['icon'] }}"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="rbac-group-label">{{ $meta['label'] }}</div>
                                                <div class="rbac-group-desc">{{ $meta['description'] }}</div>
                                            </div>
                                            <div class="rbac-group-actions" onclick="event.stopPropagation()">
                                                <span class="rbac-group-count" data-group-count="{{ $group }}">{{ $groupEnabled }}/{{ $permissions->count() }}</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 rbac-group-all" data-group="{{ $group }}">All</button>
                                                <span class="text-muted">|</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 rbac-group-none" data-group="{{ $group }}">None</button>
                                                <i class="bi bi-chevron-down text-muted ms-1"></i>
                                            </div>
                                        </div>
                                        <div class="collapse show" id="rbac-group-{{ $group }}">
                                            <div class="rbac-group-body">
                                                <div class="rbac-perm-grid">
                                                    @foreach($permissions as $permission)
                                                        <label class="rbac-perm" data-search="{{ strtolower($permission->name.' '.$permission->slug.' '.($permission->description ?? '')) }}">
                                                            <input type="checkbox" class="form-check-input rbac-perm-cb"
                                                                   name="permissions[]" value="{{ $permission->id }}"
                                                                   data-group="{{ $group }}"
                                                                   @checked(in_array($permission->id, $selectedPermIds))>
                                                            <span>
                                                                <span class="rbac-perm-name">{{ $permission->name }}</span>
                                                                <span class="rbac-perm-slug d-block">{{ $permission->slug }}</span>
                                                                @if($permission->description)
                                                                    <span class="rbac-perm-desc">{{ $permission->description }}</span>
                                                                @endif
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="rbac-footer">
                                <span class="text-muted small">Changes take effect on next request (permission cache cleared on save).</span>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2 me-1"></i>Save {{ $selected->name }}
                                </button>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('rbac-form');
    if (!form) return;

    const search = document.getElementById('rbac-search');
    const countEl = document.getElementById('rbac-enabled-count');
    const checkboxes = () => Array.from(form.querySelectorAll('.rbac-perm-cb'));

    function updateCounts() {
        const cbs = checkboxes();
        const enabled = cbs.filter(c => c.checked).length;
        if (countEl) countEl.textContent = enabled;

        form.querySelectorAll('[data-group-count]').forEach(el => {
            const g = el.getAttribute('data-group-count');
            const total = cbs.filter(c => c.dataset.group === g).length;
            const on = cbs.filter(c => c.dataset.group === g && c.checked).length;
            el.textContent = on + '/' + total;
        });
    }

    function setGroup(group, checked) {
        checkboxes().filter(c => c.dataset.group === group).forEach(c => { c.checked = checked; });
        updateCounts();
    }

    form.addEventListener('change', e => {
        if (e.target.classList.contains('rbac-perm-cb')) updateCounts();
    });

    document.getElementById('rbac-enable-all')?.addEventListener('click', () => {
        checkboxes().forEach(c => { c.checked = true; });
        updateCounts();
    });
    document.getElementById('rbac-disable-all')?.addEventListener('click', () => {
        checkboxes().forEach(c => { c.checked = false; });
        updateCounts();
    });

    form.querySelectorAll('.rbac-group-all').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            setGroup(btn.dataset.group, true);
        });
    });
    form.querySelectorAll('.rbac-group-none').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            setGroup(btn.dataset.group, false);
        });
    });

    if (search) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            form.querySelectorAll('.rbac-perm').forEach(row => {
                const text = row.getAttribute('data-search') || '';
                row.classList.toggle('hidden-by-search', q && !text.includes(q));
            });
        });
    }

    updateCounts();
})();
</script>
@endpush
