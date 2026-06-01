@php
    use App\Enums\AdminRole;
    use App\Enums\SalesTeam;
    $mgrOnly = $managerCreatesEmployeesOnly ?? false;
    $isEdit = isset($staff);
    $selectedRole = old('role', $isEdit ? $staff->role->value : AdminRole::SalesEmployee->value);
    $selectedTeam = old('sales_team', $isEdit ? ($staff->sales_team?->value ?? '') : ($lockedTeam?->value ?? SalesTeam::Candidate->value));
@endphp

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $staff->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">@if($isEdit) New password (optional) @else Password @endif</label>
    <input type="password" name="password" class="form-control" @if(! $isEdit) required @endif>
</div>
<div class="mb-3">
    <label class="form-label">Confirm password</label>
    <input type="password" name="password_confirmation" class="form-control" @if(! $isEdit) required @endif>
</div>

@if($mgrOnly)
    <div class="mb-3">
        <label class="form-label">Role</label>
        <input type="text" class="form-control" value="Sales employee" disabled>
        <div class="form-text">Managers can only add sales employees to their own team.</div>
    </div>
    @if($lockedTeam)
        <div class="mb-3">
            <label class="form-label">Sales team</label>
            <input type="text" class="form-control" value="{{ $lockedTeam->label() }}" disabled>
            <input type="hidden" name="sales_team" value="{{ $lockedTeam->value }}">
        </div>
    @endif
@else
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" id="staff-role" class="form-select" required>
            @foreach($roles as $r)
                <option value="{{ $r->value }}" @selected($selectedRole === $r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="staff-team-wrap">
        <label class="form-label">Sales team <span class="text-danger">*</span></label>
        <select name="sales_team" id="staff-sales-team" class="form-select">
            <option value="">— Not a sales role —</option>
            @foreach($salesTeams as $team)
                <option value="{{ $team->value }}" @selected($selectedTeam === $team->value)>{{ $team->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">
            <strong>Talent</strong> → Candidates pipeline (<code>/leads</code>).
            <strong>Companies</strong> → B2B pipeline (<code>/pipelines/companies</code>).
        </div>
    </div>

    <div class="mb-3" id="staff-manager-wrap">
        <label class="form-label">Reports to (sales employees)</label>
        <select name="manager_id" id="staff-manager-id" class="form-select">
            <option value="">—</option>
            @foreach($managers as $m)
                @if($isEdit && $m->id === $staff->id)
                    @continue
                @endif
                <option value="{{ $m->id }}"
                        data-sales-team="{{ $m->sales_team ?? 'candidate' }}"
                        @selected((string) old('manager_id', $staff->manager_id ?? '') === (string) $m->id)>
                    {{ $m->name }}
                    ({{ ($m->sales_team ?? 'candidate') === 'employer' ? 'Company' : 'Talent' }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Only managers on the same team are listed when a team is selected.</div>
    </div>
@endif

@if(isset($staff) && $staff->referral_code && ($staff->sales_team?->value ?? $staff->sales_team) === 'employer')
<div class="mb-3">
    <label class="form-label">Employer referral code</label>
    <div class="input-group">
        <input type="text" class="form-control font-monospace" value="{{ $staff->referral_code }}" readonly id="staff-referral-code">
        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('staff-referral-code').value)">Copy</button>
    </div>
    <div class="form-text">Share with employers at sign-up — their company is auto-assigned to this CRM user.</div>
</div>
@endif

@push('styles')
<style>
    .staff-form-card { max-width: 680px; border-radius: 16px; }
    .staff-form-hint {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: .85rem 1rem; font-size: .85rem; color: #475569;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const roleEl = document.getElementById('staff-role');
    const teamEl = document.getElementById('staff-sales-team');
    const managerEl = document.getElementById('staff-manager-id');
    if (!roleEl || !teamEl) return;

    const salesRoles = ['sales_manager', 'sales_employee'];

    function syncTeamVisibility() {
        const isSales = salesRoles.includes(roleEl.value);
        teamEl.closest('#staff-team-wrap').style.display = isSales ? '' : 'none';
        document.getElementById('staff-manager-wrap').style.display =
            (isSales && roleEl.value === 'sales_employee') ? '' : (isSales ? '' : 'none');
        if (!isSales) {
            teamEl.value = '';
        } else if (!teamEl.value) {
            teamEl.value = 'candidate';
        }
        filterManagers();
    }

    function filterManagers() {
        if (!managerEl) return;
        const team = teamEl.value;
        Array.from(managerEl.options).forEach((opt, i) => {
            if (i === 0) return;
            const mt = opt.getAttribute('data-sales-team') || 'candidate';
            opt.hidden = team && mt !== team;
        });
        const selected = managerEl.selectedOptions[0];
        if (selected && selected.hidden) {
            managerEl.value = '';
        }
    }

    roleEl.addEventListener('change', syncTeamVisibility);
    teamEl.addEventListener('change', filterManagers);
    syncTeamVisibility();
})();
</script>
@endpush
