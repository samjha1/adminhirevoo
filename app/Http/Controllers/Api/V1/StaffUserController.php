<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmRole;
use App\Services\OrgHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function __construct(
        private readonly OrgHierarchyService $hierarchy,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $this->authorizeStaffAccess($request);

        $q = Admin::query()->orderByDesc('created_at');
        $this->hierarchy->scopeManageableStaff($q, $actor);

        if ($request->filled('role')) {
            $q->where('role', $request->string('role')->toString());
        }
        if ($request->filled('q')) {
            $s = $request->string('q')->toString();
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $paginator = $q->paginate((int) $request->get('per_page', 15));

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $this->authorizeStaffAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
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
        abort_unless(in_array($role, $this->hierarchy->rolesCreatableBy($actor), true), 403);

        $team = isset($validated['sales_team']) ? SalesTeam::from($validated['sales_team']) : null;
        $region = isset($validated['sales_region']) ? SalesRegion::from($validated['sales_region']) : null;
        $managerId = isset($validated['manager_id']) ? (int) $validated['manager_id'] : null;

        if ($errors = $this->hierarchy->validateAssignment($role, $team, $region, $managerId)) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $region = $this->hierarchy->resolveRegionForRole($role, $region, $managerId);
        $crmRole = CrmRole::query()->where('slug', $role->crmRoleSlug())->first();

        $admin = Admin::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'crm_role_id' => $crmRole?->id,
            'sales_team' => $team?->value,
            'sales_region' => $region?->value,
            'manager_id' => $managerId,
        ]);

        return response()->json($admin, 201);
    }

    public function assignRole(Request $request, Admin $staff): JsonResponse
    {
        $actor = $this->authorizeStaffAccess($request);
        $this->hierarchy->assertActorCanManage($actor, $staff);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(AdminRole::class)],
            'sales_team' => ['nullable', Rule::enum(SalesTeam::class)],
            'sales_region' => ['nullable', Rule::enum(SalesRegion::class)],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        $role = AdminRole::from($validated['role']);
        $team = isset($validated['sales_team']) ? SalesTeam::from($validated['sales_team']) : null;
        $region = isset($validated['sales_region']) ? SalesRegion::from($validated['sales_region']) : null;
        $managerId = isset($validated['manager_id']) ? (int) $validated['manager_id'] : null;

        if ($errors = $this->hierarchy->validateAssignment($role, $team, $region, $managerId, $staff->id)) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $region = $this->hierarchy->resolveRegionForRole($role, $region, $managerId);
        $crmRole = CrmRole::query()->where('slug', $role->crmRoleSlug())->first();

        $staff->role = $role;
        $staff->sales_team = $team?->value;
        $staff->sales_region = $region?->value;
        $staff->manager_id = $managerId;
        $staff->crm_role_id = $crmRole?->id;
        $staff->save();

        return response()->json($staff);
    }

    private function authorizeStaffAccess(Request $request): Admin
    {
        $user = $request->user();
        abort_unless($user instanceof Admin, 403);
        abort_unless(
            $user->role?->isPlatformAdmin()
                || $user->canPermission('staff.manage'),
            403,
        );

        return $user;
    }
}
