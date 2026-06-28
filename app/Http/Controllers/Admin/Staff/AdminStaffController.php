<?php

namespace App\Http\Controllers\Admin\Staff;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Services\PermissionResolver;
use App\Services\AdminReferralCodeService;
use App\Services\OrgHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function __construct(
        private readonly OrgHierarchyService $hierarchy,
    ) {
    }

    public function index(Request $request): View
    {
        $actor = auth('admin')->user();

        $q = Admin::query()->with('manager')->orderByDesc('created_at');
        $this->hierarchy->scopeManageableStaff($q, $actor);

        if ($actor->role?->isPlatformAdmin() && $request->filled('role')) {
            $q->where('role', $request->string('role')->toString());
        }

        if ($request->filled('q')) {
            $s = $request->string('q')->toString();
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('sales_team')) {
            $q->where('sales_team', $request->string('sales_team')->toString());
        }

        if ($request->filled('sales_region')) {
            $q->where('sales_region', $request->string('sales_region')->toString());
        }

        $fieldActor = $this->hierarchy->actorCreatesEmployeesOnly($actor)
            || $this->hierarchy->actorCreatesManagersOnly($actor);

        $statsQuery = Admin::query();
        $this->hierarchy->scopeManageableStaff($statsQuery, $actor);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'sales' => (clone $statsQuery)->whereIn('role', [
                AdminRole::Asm,
                AdminRole::SalesManager,
                AdminRole::SalesEmployee,
            ])->count(),
            'managers' => (clone $statsQuery)->whereIn('role', [
                AdminRole::Asm,
                AdminRole::SalesManager,
            ])->count(),
            'platform' => (clone $statsQuery)->whereIn('role', [
                AdminRole::SuperAdmin,
                AdminRole::Admin,
                AdminRole::Marketing,
                AdminRole::Recruiter,
                AdminRole::RecruiterManager,
            ])->count(),
        ];

        return view('admin.staff.index', [
            'staff' => $q->paginate(20)->withQueryString(),
            'roles' => AdminRole::cases(),
            'salesTeams' => SalesTeam::cases(),
            'salesRegions' => SalesRegion::cases(),
            'managerCreatesEmployeesOnly' => $this->hierarchy->actorCreatesEmployeesOnly($actor),
            'asmCreatesManagersOnly' => $this->hierarchy->actorCreatesManagersOnly($actor),
            'fieldActorOnly' => $fieldActor,
            'hierarchy' => $this->hierarchy,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $actor = auth('admin')->user();

        return view('admin.staff.create', $this->formViewData($actor));
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = auth('admin')->user();

        if ($this->hierarchy->actorCreatesEmployeesOnly($actor)) {
            return $this->storeSalesEmployeeForManager($request, $actor);
        }

        if ($this->hierarchy->actorCreatesManagersOnly($actor)) {
            return $this->storeSalesManagerForAsm($request, $actor);
        }

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'sales_team' => [
                Rule::requiredIf(fn () => AdminRole::from($request->input('role', ''))->isSalesFieldRole()),
                'nullable',
                Rule::enum(SalesTeam::class),
            ],
            'sales_region' => [
                Rule::requiredIf(fn () => $request->input('role') === AdminRole::Asm->value),
                'nullable',
                Rule::enum(SalesRegion::class),
            ],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ], $this->referralCodeRules($request)));

        $role = AdminRole::from($validated['role']);
        $team = $this->resolveTeamForRole($role, $validated['sales_team'] ?? null);
        $region = $this->resolveRegionForRole($role, $validated['sales_region'] ?? null);
        $managerId = isset($validated['manager_id']) ? (int) $validated['manager_id'] : null;

        if ($errors = $this->hierarchy->validateAssignment($role, $team, $region, $managerId)) {
            return back()->withErrors($errors)->withInput();
        }

        $region = $this->hierarchy->resolveRegionForRole($role, $region, $managerId);

        $admin = $this->createStaff([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
            'sales_team' => $team,
            'sales_region' => $region,
            'manager_id' => $managerId,
            'referral_code' => $validated['referral_code'] ?? null,
        ]);

        return $this->redirectAfterStaffCreated($admin, 'Staff user created.');
    }

    private function storeSalesEmployeeForManager(Request $request, Admin $actor): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->referralCodeRules($request)));

        $admin = $this->createStaff([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => AdminRole::SalesEmployee,
            'sales_team' => $actor->sales_team ?? SalesTeam::Candidate,
            'sales_region' => $this->hierarchy->inheritRegion($actor->id),
            'manager_id' => $actor->id,
            'referral_code' => $validated['referral_code'] ?? null,
        ]);

        return $this->redirectAfterStaffCreated($admin, 'Sales employee account created.');
    }

    private function storeSalesManagerForAsm(Request $request, Admin $actor): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->referralCodeRules($request)));

        $admin = $this->createStaff([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => AdminRole::SalesManager,
            'sales_team' => $actor->sales_team ?? SalesTeam::Candidate,
            'sales_region' => $actor->sales_region ?? $this->hierarchy->inheritRegion($actor->id),
            'manager_id' => $actor->id,
            'referral_code' => $validated['referral_code'] ?? null,
        ]);

        return $this->redirectAfterStaffCreated($admin, 'Sales manager account created.');
    }

    /** @return array<string, array<int, mixed>> */
    private function referralCodeRules(Request $request): array
    {
        $rules = ['nullable', 'string', 'max:50'];

        if ($request->filled('referral_code')) {
            $rules[] = 'regex:/^[A-Za-z0-9\-]+$/';
            $rules[] = Rule::unique('admins', 'referral_code');
        }

        return ['referral_code' => $rules];
    }

    private function redirectAfterStaffCreated(Admin $admin, string $message): RedirectResponse
    {
        if ($admin->referral_code) {
            $message .= ' Referral code: '.$admin->referral_code;
        }

        $actor = auth('admin')->user();
        $url = app(PermissionResolver::class)->can($actor, 'analytics.view')
            ? route('admin.dashboard')
            : route('admin.staff.index');

        return redirect($url)->with('success', $message);
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role: AdminRole,
     *     sales_team?: ?SalesTeam,
     *     sales_region?: ?SalesRegion,
     *     manager_id: ?int,
     *     referral_code?: ?string
     * }  $data
     */
    private function createStaff(array $data): Admin
    {
        $crmRole = CrmRole::query()->where('slug', $data['role']->crmRoleSlug())->first();
        $team = $data['sales_team'] ?? $this->defaultTeamForRole($data['role']);
        $region = $data['sales_region'] ?? $this->hierarchy->resolveRegionForRole(
            $data['role'],
            null,
            $data['manager_id'],
        );

        $admin = Admin::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'crm_role_id' => $crmRole?->id,
            'sales_team' => $team?->value,
            'sales_region' => $region?->value,
            'manager_id' => $data['manager_id'],
        ]);

        app(AdminReferralCodeService::class)->assignCode($admin, $data['referral_code'] ?? null);

        return $admin->fresh();
    }

    private function defaultTeamForRole(AdminRole $role): ?SalesTeam
    {
        return match ($role) {
            AdminRole::Asm, AdminRole::SalesManager, AdminRole::SalesEmployee => SalesTeam::Candidate,
            default => null,
        };
    }

    private function resolveTeamForRole(AdminRole $role, mixed $teamValue): ?SalesTeam
    {
        if (! $role->isSalesFieldRole()) {
            return null;
        }

        if ($teamValue instanceof SalesTeam) {
            return $teamValue;
        }

        if (is_string($teamValue) && $teamValue !== '') {
            return SalesTeam::from($teamValue);
        }

        return $this->defaultTeamForRole($role);
    }

    private function resolveRegionForRole(AdminRole $role, mixed $regionValue): ?SalesRegion
    {
        if ($role !== AdminRole::Asm) {
            return null;
        }

        if ($regionValue instanceof SalesRegion) {
            return $regionValue;
        }

        if (is_string($regionValue) && $regionValue !== '') {
            return SalesRegion::from($regionValue);
        }

        return null;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function managerOptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->whereIn('role', [
                AdminRole::SuperAdmin,
                AdminRole::Admin,
                AdminRole::Asm,
                AdminRole::SalesManager,
            ])
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, mixed> */
    private function formViewData(Admin $actor, ?Admin $staff = null): array
    {
        $creatableRoles = $this->hierarchy->rolesCreatableBy($actor);
        $employeeOnly = $this->hierarchy->actorCreatesEmployeesOnly($actor);
        $managerOnly = $this->hierarchy->actorCreatesManagersOnly($actor);

        return [
            'staff' => $staff,
            'roles' => $employeeOnly
                ? [AdminRole::SalesEmployee]
                : ($managerOnly ? [AdminRole::SalesManager] : ($creatableRoles === AdminRole::cases() ? AdminRole::cases() : $creatableRoles)),
            'managers' => ($employeeOnly || $managerOnly) ? collect() : $this->managerOptions(),
            'salesTeams' => SalesTeam::cases(),
            'salesRegions' => SalesRegion::cases(),
            'managerCreatesEmployeesOnly' => $employeeOnly,
            'asmCreatesManagersOnly' => $managerOnly,
            'lockedTeam' => ($employeeOnly || $managerOnly) ? ($actor->sales_team ?? SalesTeam::Candidate) : null,
            'lockedRegion' => $managerOnly ? ($actor->sales_region ?? $actor->resolvedRegion()) : null,
        ];
    }

    public function edit(Admin $staff): View
    {
        $actor = auth('admin')->user();
        $this->hierarchy->assertActorCanManage($actor, $staff);

        return view('admin.staff.edit', array_merge($this->formViewData($actor, $staff), [
            'readOnly' => ! $this->hierarchy->actorCanEditStaff($actor, $staff),
        ]));
    }

    public function update(Request $request, Admin $staff): RedirectResponse
    {
        $actor = auth('admin')->user();
        $this->hierarchy->assertActorCanManage($actor, $staff);

        if (! $this->hierarchy->actorCanEditStaff($actor, $staff)) {
            abort(403, 'You can view this staff member but cannot edit them.');
        }

        if ($this->hierarchy->actorCreatesEmployeesOnly($actor)) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($staff->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            $staff->name = $validated['name'];
            $staff->email = $validated['email'];
            if (! empty($validated['password'])) {
                $staff->password = $validated['password'];
            }
            $staff->save();
            app(AdminReferralCodeService::class)->ensureCode($staff->fresh());

            return redirect()->route('admin.staff.index')->with('success', 'Team member updated.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'sales_team' => [
                Rule::requiredIf(fn () => AdminRole::from($request->input('role', ''))->isSalesFieldRole()),
                'nullable',
                Rule::enum(SalesTeam::class),
            ],
            'sales_region' => [
                Rule::requiredIf(fn () => $request->input('role') === AdminRole::Asm->value),
                'nullable',
                Rule::enum(SalesRegion::class),
            ],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        $role = AdminRole::from($validated['role']);
        $team = $this->resolveTeamForRole($role, $validated['sales_team'] ?? null);
        $region = $this->resolveRegionForRole($role, $validated['sales_region'] ?? null);
        $managerId = isset($validated['manager_id']) ? (int) $validated['manager_id'] : null;

        if ($errors = $this->hierarchy->validateAssignment($role, $team, $region, $managerId, $staff->id)) {
            return back()->withErrors($errors)->withInput();
        }

        $region = $this->hierarchy->resolveRegionForRole($role, $region, $managerId);

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->role = $role;
        $staff->sales_team = $team?->value;
        $staff->sales_region = $region?->value;
        $staff->manager_id = $managerId;
        if (! empty($validated['password'])) {
            $staff->password = $validated['password'];
        }

        $crmRole = CrmRole::query()->where('slug', $role->crmRoleSlug())->first();
        $staff->crm_role_id = $crmRole?->id;
        $staff->save();
        app(AdminReferralCodeService::class)->ensureCode($staff->fresh());

        return redirect()->route('admin.staff.index')->with('success', 'Staff user updated.');
    }

    public function destroy(Admin $staff): RedirectResponse
    {
        $actor = auth('admin')->user();
        $this->hierarchy->assertActorCanManage($actor, $staff);

        if (! $this->hierarchy->actorCanEditStaff($actor, $staff)) {
            abort(403, 'You cannot delete this staff member.');
        }

        if ($staff->id === $actor->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff user removed.');
    }
}
