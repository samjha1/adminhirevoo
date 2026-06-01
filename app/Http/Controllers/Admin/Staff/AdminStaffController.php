<?php

namespace App\Http\Controllers\Admin\Staff;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use App\Services\AdminReferralCodeService;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function index(Request $request): View
    {
        $actor = auth('admin')->user();

        $q = Admin::query()->orderByDesc('created_at');

        if ($actor->role === AdminRole::SalesManager) {
            $q->where('role', AdminRole::SalesEmployee)
                ->where('manager_id', $actor->id);
        } elseif ($request->filled('role')) {
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

        return view('admin.staff.index', [
            'staff' => $q->paginate(20)->withQueryString(),
            'roles' => AdminRole::cases(),
            'salesTeams' => SalesTeam::cases(),
            'managerCreatesEmployeesOnly' => $actor->role === AdminRole::SalesManager,
        ]);
    }

    public function create(): View
    {
        $actor = auth('admin')->user();
        $managerOnly = $actor->role === AdminRole::SalesManager;

        return view('admin.staff.create', [
            'roles' => $managerOnly ? [AdminRole::SalesEmployee] : AdminRole::cases(),
            'managers' => $managerOnly ? collect() : $this->salesManagers(),
            'salesTeams' => SalesTeam::cases(),
            'managerCreatesEmployeesOnly' => $managerOnly,
            'lockedTeam' => $managerOnly ? ($actor->sales_team ?? SalesTeam::Candidate) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = auth('admin')->user();

        if ($actor->role === AdminRole::SalesManager) {
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
                'manager_id' => $actor->id,
                'referral_code' => $validated['referral_code'] ?? null,
            ]);

            return $this->redirectAfterStaffCreated($admin, 'Sales employee account created.');
        }

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'sales_team' => [
                Rule::requiredIf(fn () => in_array($request->input('role'), [
                    AdminRole::SalesManager->value,
                    AdminRole::SalesEmployee->value,
                ], true)),
                'nullable',
                Rule::enum(SalesTeam::class),
            ],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ], $this->referralCodeRules($request)));

        $role = AdminRole::from($validated['role']);
        $team = $this->resolveTeamForRole($role, $validated['sales_team'] ?? null);

        if ($teamError = $this->validateTeamAndManager($role, $team, isset($validated['manager_id']) ? (int) $validated['manager_id'] : null)) {
            return back()->withErrors($teamError)->withInput();
        }

        $admin = $this->createStaff([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
            'sales_team' => $team,
            'manager_id' => $validated['manager_id'] ?? null,
            'referral_code' => $validated['referral_code'] ?? null,
        ]);

        return $this->redirectAfterStaffCreated($admin, 'Staff user created.');
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

    /** @param  array{name: string, email: string, password: string, role: AdminRole, sales_team?: ?SalesTeam, manager_id: ?int, referral_code?: ?string}  $data */
    private function createStaff(array $data): Admin
    {
        $crmRole = CrmRole::query()->where('slug', $data['role']->crmRoleSlug())->first();
        $team = $data['sales_team'] ?? $this->defaultTeamForRole($data['role']);

        $admin = Admin::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'crm_role_id' => $crmRole?->id,
            'sales_team' => $team?->value,
            'manager_id' => $data['manager_id'],
        ]);

        app(AdminReferralCodeService::class)->assignCode($admin, $data['referral_code'] ?? null);

        return $admin->fresh();
    }

    private function defaultTeamForRole(AdminRole $role): ?SalesTeam
    {
        return match ($role) {
            AdminRole::SalesManager, AdminRole::SalesEmployee => SalesTeam::Candidate,
            default => null,
        };
    }

    private function resolveTeamForRole(AdminRole $role, mixed $teamValue): ?SalesTeam
    {
        if (! in_array($role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true)) {
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

    /** @return array<string, string> */
    private function validateTeamAndManager(AdminRole $role, ?SalesTeam $team, ?int $managerId): array
    {
        $errors = [];

        if (in_array($role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true) && $team === null) {
            $errors['sales_team'] = 'Choose Talent or Company team for sales roles.';
        }

        if ($role === AdminRole::SalesEmployee && empty($managerId)) {
            $errors['manager_id'] = 'Sales employees must report to a manager.';
        }

        if ($managerId && $team) {
            $manager = Admin::query()->find($managerId);
            if ($manager && $manager->role === AdminRole::SalesManager) {
                $employeeTeam = $team->value;
                $managerTeam = SalesTeam::normalize($manager->sales_team);

                if ($managerTeam !== $employeeTeam) {
                    $managerLabel = SalesTeam::from($managerTeam)->shortLabel();
                    $errors['manager_id'] = 'Selected manager is on the '.$managerLabel
                        .'. Choose a manager from the '.$team->shortLabel()
                        .', or change the sales team above.';
                }
            }
        }

        return $errors;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Admin> */
    private function salesManagers(): \Illuminate\Database\Eloquent\Collection
    {
        return Admin::query()
            ->where('role', AdminRole::SalesManager)
            ->orderBy('name')
            ->get();
    }

    public function edit(Admin $staff): View
    {
        $this->assertManagerCanManageStaff($staff);

        $actor = auth('admin')->user();
        $managerOnly = $actor->role === AdminRole::SalesManager;

        return view('admin.staff.edit', [
            'staff' => $staff,
            'roles' => $managerOnly ? [AdminRole::SalesEmployee] : AdminRole::cases(),
            'managers' => $managerOnly ? collect() : $this->salesManagers(),
            'salesTeams' => SalesTeam::cases(),
            'managerCreatesEmployeesOnly' => $managerOnly,
            'lockedTeam' => $managerOnly ? ($actor->sales_team ?? SalesTeam::Candidate) : null,
        ]);
    }

    public function update(Request $request, Admin $staff): RedirectResponse
    {
        $this->assertManagerCanManageStaff($staff);

        $actor = auth('admin')->user();

        if ($actor->role === AdminRole::SalesManager) {
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
                Rule::requiredIf(fn () => in_array($request->input('role'), [
                    AdminRole::SalesManager->value,
                    AdminRole::SalesEmployee->value,
                ], true)),
                'nullable',
                Rule::enum(SalesTeam::class),
            ],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        $role = AdminRole::from($validated['role']);
        $team = $this->resolveTeamForRole($role, $validated['sales_team'] ?? null);

        if ($teamError = $this->validateTeamAndManager($role, $team, isset($validated['manager_id']) ? (int) $validated['manager_id'] : null)) {
            return back()->withErrors($teamError)->withInput();
        }

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->role = $role;
        $staff->sales_team = $team?->value;
        $staff->manager_id = $validated['manager_id'] ?? null;
        if (! empty($validated['password'])) {
            $staff->password = $validated['password'];
        }
        $staff->save();
        app(AdminReferralCodeService::class)->ensureCode($staff->fresh());

        return redirect()->route('admin.staff.index')->with('success', 'Staff user updated.');
    }

    public function destroy(Admin $staff): RedirectResponse
    {
        $this->assertManagerCanManageStaff($staff);

        if ($staff->id === auth('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff user removed.');
    }

    private function assertManagerCanManageStaff(Admin $staff): void
    {
        $actor = auth('admin')->user();

        if ($actor->role === AdminRole::Admin || $actor->role === AdminRole::SuperAdmin) {
            return;
        }

        if ($actor->role === AdminRole::SalesManager) {
            abort_unless(
                $staff->role === AdminRole::SalesEmployee && (int) $staff->manager_id === (int) $actor->id,
                403,
                'You can only manage sales employees on your team.'
            );

            return;
        }

        abort(403);
    }
}
