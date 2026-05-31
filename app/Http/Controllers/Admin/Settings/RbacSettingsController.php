<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RbacSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $roles = CrmRole::query()->with('permissions')->orderBy('name')->get();
        $permissions = CrmPermission::query()->orderBy('group')->orderBy('name')->get();
        $permissionsByGroup = $permissions->groupBy('group');

        $adminCounts = Admin::query()
            ->whereNotNull('crm_role_id')
            ->selectRaw('crm_role_id, COUNT(*) as aggregate')
            ->groupBy('crm_role_id')
            ->pluck('aggregate', 'crm_role_id');

        $selected = $roles->firstWhere('slug', $request->string('role')->toString())
            ?? $roles->first(fn (CrmRole $r) => $r->slug !== 'super_admin')
            ?? $roles->first();

        return view('admin.settings.rbac.index', [
            'roles' => $roles,
            'permissionsByGroup' => $permissionsByGroup,
            'totalPermissions' => $permissions->count(),
            'permissionGroups' => $this->permissionGroupMeta(),
            'adminCountsByRole' => $adminCounts,
            'selectedRole' => $selected,
        ]);
    }

    /** @return array<string, array{label: string, icon: string, description: string, order: int}> */
    private function permissionGroupMeta(): array
    {
        return [
            'leads' => [
                'label' => 'Leads & pipeline',
                'icon' => 'bi-funnel',
                'description' => 'Candidates, assignments, kanban, calls, and follow-ups.',
                'order' => 1,
            ],
            'staff' => [
                'label' => 'Team & staff',
                'icon' => 'bi-people',
                'description' => 'Who can view or create admin logins.',
                'order' => 2,
            ],
            'analytics' => [
                'label' => 'Dashboards',
                'icon' => 'bi-grid-1x2',
                'description' => 'Home dashboards and KPI access.',
                'order' => 3,
            ],
            'rbac' => [
                'label' => 'Security',
                'icon' => 'bi-shield-lock',
                'description' => 'Roles and permission matrix (this page).',
                'order' => 4,
            ],
            'platform' => [
                'label' => 'Platform ops',
                'icon' => 'bi-sliders',
                'description' => 'Hirevo users, jobs, employers, referrals, payments.',
                'order' => 5,
            ],
            'settings' => [
                'label' => 'Settings',
                'icon' => 'bi-gear',
                'description' => 'General configuration access.',
                'order' => 6,
            ],
        ];
    }

    public function update(Request $request, CrmRole $role, PermissionResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:crm_permissions,id'],
        ]);

        if ($role->is_system && $role->slug === 'super_admin') {
            return back()->with('error', 'Super Admin permissions cannot be edited.');
        }

        $role->permissions()->sync($validated['permissions'] ?? []);
        $resolver->forgetAll();

        return redirect()
            ->route('admin.settings.rbac', ['role' => $role->slug])
            ->with('success', "Permissions updated for {$role->name}.");
    }
}
