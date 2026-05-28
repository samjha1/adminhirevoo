<?php

namespace App\Http\Controllers\Admin\Staff;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        return view('admin.staff.index', [
            'staff' => $q->paginate(20)->withQueryString(),
            'roles' => AdminRole::cases(),
            'managerCreatesEmployeesOnly' => $actor->role === AdminRole::SalesManager,
        ]);
    }

    public function create(): View
    {
        $actor = auth('admin')->user();
        $managerOnly = $actor->role === AdminRole::SalesManager;

        return view('admin.staff.create', [
            'roles' => $managerOnly ? [AdminRole::SalesEmployee] : AdminRole::cases(),
            'managers' => $managerOnly ? collect() : Admin::query()->where('role', AdminRole::SalesManager)->orderBy('name')->get(),
            'managerCreatesEmployeesOnly' => $managerOnly,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = auth('admin')->user();

        if ($actor->role === AdminRole::SalesManager) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            Admin::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => AdminRole::SalesEmployee,
                'manager_id' => $actor->id,
            ]);

            return redirect()->route('admin.staff.index')->with('success', 'Sales employee account created.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        if ($validated['role'] === AdminRole::SalesEmployee->value && empty($validated['manager_id'])) {
            return back()->withErrors(['manager_id' => 'Sales employees must report to a manager.'])->withInput();
        }

        Admin::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'manager_id' => $validated['manager_id'] ?? null,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff user created.');
    }

    public function edit(Admin $staff): View
    {
        $this->assertManagerCanManageStaff($staff);

        $actor = auth('admin')->user();
        $managerOnly = $actor->role === AdminRole::SalesManager;

        return view('admin.staff.edit', [
            'staff' => $staff,
            'roles' => $managerOnly ? [AdminRole::SalesEmployee] : AdminRole::cases(),
            'managers' => $managerOnly ? collect() : Admin::query()->where('role', AdminRole::SalesManager)->orderBy('name')->get(),
            'managerCreatesEmployeesOnly' => $managerOnly,
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
                $staff->password = Hash::make($validated['password']);
            }
            $staff->save();

            return redirect()->route('admin.staff.index')->with('success', 'Team member updated.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        if ($validated['role'] === AdminRole::SalesEmployee->value && empty($validated['manager_id'])) {
            return back()->withErrors(['manager_id' => 'Sales employees must report to a manager.'])->withInput();
        }

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->role = AdminRole::from($validated['role']);
        $staff->manager_id = $validated['manager_id'] ?? null;
        if (! empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }
        $staff->save();

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

        if ($actor->role === AdminRole::Admin) {
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
