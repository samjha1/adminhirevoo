<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Models\CrmRole;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RbacSettingsController extends Controller
{
    public function index(): View
    {
        $roles = CrmRole::query()->with('permissions')->orderBy('name')->get();
        $permissions = CrmPermission::query()->orderBy('group')->orderBy('name')->get()
            ->groupBy('group');

        return view('admin.settings.rbac.index', [
            'roles' => $roles,
            'permissionsByGroup' => $permissions,
        ]);
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

        return back()->with('success', "Permissions updated for {$role->name}.");
    }
}
