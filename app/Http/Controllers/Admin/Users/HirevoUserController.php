<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HirevoUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = HirevoUser::query()->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function updateStatus(Request $request, HirevoUser $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,blocked,pending'],
        ]);

        $user->status = $validated['status'];
        $user->save();

        return back()->with('success', "User {$user->name} status updated.");
    }
}
