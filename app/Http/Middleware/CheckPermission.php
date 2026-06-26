<?php

namespace App\Http\Middleware;

use App\Modules\Rbac\Services\PermissionResolver;
use App\Support\AdminHomeResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly PermissionResolver $resolver,
    ) {
    }

    /**
     * Usage: middleware('permission:leads.view|leads.view_all')
     */
    public function handle(Request $request, Closure $next, string $permissionList): Response
    {
        $admin = $request->user('admin') ?? $request->user();

        if (! $admin) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login');
        }

        if ($admin->isSuperAdmin()) {
            return $next($request);
        }

        $slugs = array_map('trim', explode('|', $permissionList));

        foreach ($slugs as $slug) {
            if ($this->resolver->can($admin, $slug)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($request->isMethod('GET')) {
            $home = AdminHomeResolver::urlFor($admin);

            if ($request->fullUrl() !== $home) {
                return redirect($home)->with(
                    'error',
                    'You do not have permission to access that area.',
                );
            }
        }

        abort(403, 'You do not have permission to access this area.');
    }
}
