<?php

namespace App\Providers;

use App\Models\Hirevo\HirevoLead;
use App\Models\Leadsmanager\LeadsmanagerAd;
use App\Policies\LeadPolicy;
use Illuminate\Support\Facades\Gate;
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

        Route::bind('ad', fn (string $value) => LeadsmanagerAd::findOrFail($value));
    }
}
