@php
    use App\Enums\AdminRole;
    use App\Enums\SalesRegion;
    use App\Enums\SalesTeam;
    $mgrOnly = $managerCreatesEmployeesOnly ?? false;
    $asmOnly = $asmCreatesManagersOnly ?? false;
    $isEdit = isset($staff);
    $selectedRole = old('role', $isEdit ? $staff->role->value : ($asmOnly ? AdminRole::SalesManager->value : AdminRole::SalesEmployee->value));
    $selectedTeam = old('sales_team', $isEdit ? ($staff->sales_team?->value ?? '') : ($lockedTeam?->value ?? SalesTeam::Candidate->value));
    $selectedRegion = old('sales_region', $isEdit ? ($staff->sales_region?->value ?? '') : ($lockedRegion?->value ?? SalesRegion::North->value));
@endphp

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $staff->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email ?? '') }}" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">@if($isEdit) New password (optional) @else Password @endif</label>
    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if(! $isEdit) required @endif>
    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label">Confirm password</label>
    <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" @if(! $isEdit) required @endif>
    @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
@elseif($asmOnly)
    <div class="mb-3">
        <label class="form-label">Role</label>
        <input type="text" class="form-control" value="Sales manager" disabled>
        <div class="form-text">ASMs can only add sales managers to their region.</div>
    </div>
    @if($lockedTeam)
        <div class="mb-3">
            <label class="form-label">Sales team</label>
            <input type="text" class="form-control" value="{{ $lockedTeam->label() }}" disabled>
        </div>
    @endif
    @if($lockedRegion)
        <div class="mb-3">
            <label class="form-label">Region</label>
            <input type="text" class="form-control" value="{{ $lockedRegion->label() }}" disabled>
        </div>
    @endif
@else
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" id="staff-role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach($roles as $r)
                <option value="{{ $r->value }}" @selected($selectedRole === $r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3" id="staff-team-wrap">
        <label class="form-label">Sales team <span class="text-danger">*</span></label>
        <select name="sales_team" id="staff-sales-team" class="form-select @error('sales_team') is-invalid @enderror">
            <option value="">— Not a sales role —</option>
            @foreach($salesTeams as $team)
                <option value="{{ $team->value }}" @selected($selectedTeam === $team->value)>{{ $team->label() }}</option>
            @endforeach
        </select>
        <div class="form-text">
            <strong>Talent</strong> → Candidates pipeline.
            <strong>Companies</strong> → B2B pipeline.
        </div>
        @error('sales_team')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3" id="staff-region-wrap">
        <label class="form-label">Region <span class="text-danger" id="staff-region-required">*</span></label>
        <select name="sales_region" id="staff-sales-region" class="form-select @error('sales_region') is-invalid @enderror">
            <option value="">—</option>
            @foreach($salesRegions as $region)
                <option value="{{ $region->value }}" @selected($selectedRegion === $region->value)>{{ $region->label() }}</option>
            @endforeach
        </select>
        <div class="form-text" id="staff-region-hint">Required for ASM (e.g. ASM Talent North, ASM Company South).</div>
        @error('sales_region')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3" id="staff-manager-wrap">
        <label class="form-label">Reports to <span class="text-danger" id="staff-manager-required">*</span></label>
        <select name="manager_id" id="staff-manager-id" class="form-select @error('manager_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach($managers as $m)
                @if($isEdit && $m->id === $staff->id)
                    @continue
                @endif
                @php
                    $mgrTeam = SalesTeam::normalize($m->sales_team);
                    $mgrRegion = SalesRegion::normalize($m->sales_region) ?? '';
                @endphp
                <option value="{{ $m->id }}"
                        data-role="{{ $m->role->value }}"
                        data-sales-team="{{ $mgrTeam }}"
                        data-sales-region="{{ $mgrRegion }}"
                        @selected((string) old('manager_id', $staff->manager_id ?? '') === (string) $m->id)>
                    {{ $m->name }}
                    ({{ $m->role->label() }}{{ $mgrTeam ? ' · '.($mgrTeam === 'employer' ? 'Company' : 'Talent') : '' }}{{ $mgrRegion ? ' · '.ucfirst($mgrRegion) : '' }})
                </option>
            @endforeach
        </select>
        <div class="form-text" id="staff-manager-hint">ASM reports to Admin. Sales managers report to ASM. Employees report to sales manager.</div>
        @error('manager_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
@endif

@php
    $showReferralOnCreate = ! $isEdit && (
        ($mgrOnly && ($lockedTeam?->value ?? '') === 'employer')
        || ($asmOnly && ($lockedTeam?->value ?? '') === 'employer')
        || (! $mgrOnly && ! $asmOnly && in_array($selectedRole, ['sales_manager', 'sales_employee', 'asm'], true) && $selectedTeam === 'employer')
    );
@endphp
@if($showReferralOnCreate)
<div class="mb-3" id="staff-referral-wrap">
    <label class="form-label" for="staff-referral-code-input">Employer referral code</label>
    <div class="input-group">
        <input type="text"
               name="referral_code"
               id="staff-referral-code-input"
               class="form-control font-monospace @error('referral_code') is-invalid @enderror"
               value="{{ old('referral_code') }}"
               placeholder="Leave blank to auto-generate"
               maxlength="50"
               autocomplete="off">
        <button type="button" class="btn btn-outline-secondary" id="staff-referral-generate" title="Generate a unique code">Generate</button>
    </div>
    @error('referral_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <div class="form-text">Share with employers at Hirevo sign-up. Empty = auto-generated (e.g. EMP-ABC123).</div>
</div>
@elseif(! $isEdit && ! $asmOnly)
<div class="mb-3" id="staff-referral-wrap" style="display: none;">
    <label class="form-label" for="staff-referral-code-input">Employer referral code</label>
    <div class="input-group">
        <input type="text"
               name="referral_code"
               id="staff-referral-code-input"
               class="form-control font-monospace @error('referral_code') is-invalid @enderror"
               value="{{ old('referral_code') }}"
               placeholder="Leave blank to auto-generate"
               maxlength="50"
               autocomplete="off">
        <button type="button" class="btn btn-outline-secondary" id="staff-referral-generate">Generate</button>
    </div>
    @error('referral_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <div class="form-text">For <strong>Company</strong> sales roles only. Leave blank to auto-generate.</div>
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
    const regionEl = document.getElementById('staff-sales-region');
    const managerEl = document.getElementById('staff-manager-id');
    const referralWrap = document.getElementById('staff-referral-wrap');
    const referralInput = document.getElementById('staff-referral-code-input');
    const referralGenerate = document.getElementById('staff-referral-generate');
    if (!roleEl || !teamEl) return;

    const salesRoles = ['asm', 'sales_manager', 'sales_employee'];
    const managerRolesByStaffRole = {
        asm: ['super_admin', 'admin'],
        sales_manager: ['asm'],
        sales_employee: ['sales_manager'],
    };

    function syncReferralVisibility() {
        if (!referralWrap) return;
        const isEmployerSales = salesRoles.includes(roleEl.value) && teamEl.value === 'employer';
        referralWrap.style.display = isEmployerSales ? '' : 'none';
        if (!isEmployerSales && referralInput) {
            referralInput.value = '';
        }
    }

    const managerRequired = document.getElementById('staff-manager-required');
    const managerElWrap = document.getElementById('staff-manager-wrap');
    const regionWrap = document.getElementById('staff-region-wrap');
    const regionRequired = document.getElementById('staff-region-required');

    function syncTeamVisibility() {
        const role = roleEl.value;
        const isSales = salesRoles.includes(role);
        const isAsm = role === 'asm';
        const needsManager = role === 'sales_manager' || role === 'sales_employee' || role === 'asm';

        teamEl.closest('#staff-team-wrap').style.display = isSales ? '' : 'none';
        if (regionWrap) {
            regionWrap.style.display = isAsm ? '' : 'none';
        }
        if (regionEl) {
            regionEl.required = isAsm;
        }
        if (regionRequired) {
            regionRequired.style.display = isAsm ? '' : 'none';
        }
        if (managerElWrap) {
            managerElWrap.style.display = needsManager ? '' : 'none';
        }
        if (managerEl) {
            managerEl.required = needsManager;
        }
        if (managerRequired) {
            managerRequired.style.display = needsManager ? '' : 'none';
        }
        if (!isSales) {
            teamEl.value = '';
            if (regionEl) regionEl.value = '';
        } else if (!teamEl.value) {
            teamEl.value = 'candidate';
        }
        filterManagers();
        syncReferralVisibility();
    }

    referralGenerate?.addEventListener('click', function () {
        if (!referralInput) return;
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let suffix = '';
        for (let i = 0; i < 6; i++) {
            suffix += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        referralInput.value = 'EMP-' + suffix;
    });

    function filterManagers() {
        if (!managerEl) return;
        const role = roleEl.value;
        const team = teamEl.value;
        const region = regionEl ? regionEl.value : '';
        const allowedRoles = managerRolesByStaffRole[role] || [];

        Array.from(managerEl.options).forEach((opt, i) => {
            if (i === 0) return;
            const optRole = opt.getAttribute('data-role') || '';
            const optTeam = opt.getAttribute('data-sales-team') || 'candidate';
            const optRegion = opt.getAttribute('data-sales-region') || '';

            let visible = allowedRoles.includes(optRole);
            if (visible && team && (role === 'sales_manager' || role === 'sales_employee')) {
                visible = optTeam === team;
            }
            if (visible && role === 'sales_manager' && region) {
                visible = optRegion === region;
            }
            opt.hidden = !visible;
        });

        const selected = managerEl.selectedOptions[0];
        if (selected && selected.hidden) {
            managerEl.value = '';
        }
    }

    roleEl.addEventListener('change', syncTeamVisibility);
    teamEl.addEventListener('change', function () {
        filterManagers();
        syncReferralVisibility();
    });
    regionEl?.addEventListener('change', filterManagers);
    syncTeamVisibility();
})();
</script>
@endpush
