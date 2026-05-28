<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeStaffAdmin($request);

        $q = Admin::query()->orderByDesc('created_at');

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
        $this->authorizeStaffAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        if ($validated['role'] === AdminRole::SalesEmployee && empty($validated['manager_id'])) {
            return response()->json(['message' => 'Sales employees require manager_id.'], 422);
        }

        $admin = Admin::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'manager_id' => $validated['manager_id'] ?? null,
        ]);

        return response()->json($admin, 201);
    }

    public function assignRole(Request $request, Admin $staff): JsonResponse
    {
        $this->authorizeStaffAdmin($request);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(AdminRole::class)],
            'manager_id' => ['nullable', 'exists:admins,id'],
        ]);

        if ($validated['role'] === AdminRole::SalesEmployee && empty($validated['manager_id'])) {
            return response()->json(['message' => 'Sales employees require manager_id.'], 422);
        }

        $staff->role = $validated['role'];
        $staff->manager_id = $validated['manager_id'] ?? null;
        $staff->save();

        return response()->json($staff);
    }

    private function authorizeStaffAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user instanceof Admin && $user->role === AdminRole::Admin, 403);
    }
}
