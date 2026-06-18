<?php

namespace App\Providers;

use App\Models\Hirevo\HirevoLead;
use App\Models\Leadsmanager\LeadsmanagerAd;
use App\Policies\LeadPolicy;
use App\Services\EmployerPlanPaymentVisibilityService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(HirevoLead::class, LeadPolicy::class);

        View::composer('partials.admin-sidebar-nav', function ($view) {
            $admin = auth('admin')->user();
            if ($admin === null) {
                $view->with('employerPlanPaymentsPending', 0);
                $view->with('sponsoredAdsPending', 0);

                return;
            }

            $view->with(
                'employerPlanPaymentsPending',
                app(EmployerPlanPaymentVisibilityService::class)->pendingCountFor($admin),
            );

            $sponsoredAdsPending = 0;
            if ($admin->canPermission('platform.sponsored_ads') && Schema::hasTable('leadsmanager_ads')) {
                $sponsoredAdsPending = LeadsmanagerAd::query()
                    ->whereIn('status', LeadsmanagerAd::REVIEW_STATUSES)
                    ->count();
            }

            $view->with('sponsoredAdsPending', $sponsoredAdsPending);
        });

        Route::bind('ad', fn (string $value) => LeadsmanagerAd::findOrFail($value));
    }
}
