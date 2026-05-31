<?php

namespace App\Modules\Rbac\Providers;

use App\Models\Admin;
use App\Modules\Rbac\Models\CrmPermission;
use App\Modules\Rbac\Services\PermissionResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionResolver::class);
    }

    public function boot(): void
    {
        Gate::before(function (Admin $admin, string $ability) {
            if ($admin->role?->isSuperAdmin()) {
                // Lead assignment policies enforce ownership and assignment state.
                if (in_array($ability, ['takeBack', 'assignAsManager', 'assignAsMarketing'], true)) {
                    return null;
                }

                return true;
            }

            return null;
        });

        if (! $this->app->runningInConsole() || $this->tablesExist()) {
            $this->registerDynamicGates();
        }
    }

    private function tablesExist(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('crm_permissions');
        } catch (\Throwable) {
            return false;
        }
    }

    private function registerDynamicGates(): void
    {
        try {
            CrmPermission::query()->pluck('slug')->each(function (string $slug): void {
                Gate::define($slug, function (Admin $admin) use ($slug) {
                    return app(PermissionResolver::class)->can($admin, $slug);
                });
            });
        } catch (\Throwable) {
            // Migrations not run yet.
        }
    }
}
