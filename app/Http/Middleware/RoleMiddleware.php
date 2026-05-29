<?php

namespace App\Http\Middleware;

use App\Enums\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Usage: middleware('role:admin|marketing|sales_manager|sales_employee')
     */
    public function handle(Request $request, Closure $next, string $roleList): Response
    {
        $admin = $request->user('admin') ?? $request->user();

        if (! $admin) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login');
        }

        $allowed = array_map('trim', explode('|', $roleList));

        foreach ($allowed as $value) {
            $role = AdminRole::from($value);
            if ($admin->role === $role) {
                return $next($request);
            }
            // super_admin may access legacy admin-only routes
            if ($value === 'admin' && $admin->role === AdminRole::SuperAdmin) {
                return $next($request);
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Forbidden.'], 403)
            : abort(403, 'You do not have access to this area.');
    }
}
