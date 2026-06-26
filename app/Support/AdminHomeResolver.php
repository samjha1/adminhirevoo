<?php

namespace App\Support;

use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Modules\Rbac\Services\PermissionResolver;
use App\Services\SalesTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminHomeResolver
{
    public static function routeNameFor(Admin $admin): string
    {
        $resolver = app(PermissionResolver::class);

        if ($resolver->can($admin, 'analytics.view')) {
            return 'admin.dashboard';
        }

        if ($resolver->can($admin, 'portal.dashboard.view')) {
            return 'admin.portal.dashboard';
        }

        if ($resolver->can($admin, 'portal.jobs.view')) {
            return 'admin.jobs.index';
        }

        if ($resolver->can($admin, 'portal.applications.view')) {
            return 'admin.portal.applications.index';
        }

        if ($resolver->can($admin, 'leads.view')) {
            return self::pipelineRouteNameFor($admin);
        }

        if ($resolver->can($admin, 'portal.candidates.view')) {
            return 'admin.candidates.index';
        }

        if ($resolver->can($admin, 'portal.companies.view')) {
            return 'admin.employers.index';
        }

        return 'admin.dashboard';
    }

    public static function urlFor(Admin $admin): string
    {
        return route(self::routeNameFor($admin));
    }

    public static function loginRedirect(Admin $admin, Request $request): RedirectResponse
    {
        $home = self::urlFor($admin);
        $intended = $request->session()->pull('url.intended');

        if ($intended && self::isAllowedIntendedUrl($admin, $intended, $request)) {
            return redirect()->to($intended);
        }

        return redirect()->to($home);
    }

    private static function isAllowedIntendedUrl(Admin $admin, string $url, Request $request): bool
    {
        $intendedHost = parse_url($url, PHP_URL_HOST);
        if ($intendedHost !== null && $intendedHost !== $request->getHost()) {
            return false;
        }

        $path = rtrim(parse_url($url, PHP_URL_PATH) ?? '/', '/') ?: '/';
        $resolver = app(PermissionResolver::class);

        $blocked = [
            '/dashboard' => ['analytics.view'],
            '/portal' => ['portal.dashboard.view'],
            '/staff' => ['staff.view', 'staff.manage'],
            '/settings/audit-logs' => ['audit.view'],
            '/settings/roles' => ['rbac.manage_permissions'],
            '/reports' => ['portal.reports.view'],
            '/leads' => ['leads.view'],
            '/pipelines/companies' => ['leads.view'],
            '/employer-plan-payments' => ['employer_payments.view'],
            '/marketing-leads' => ['leads.import', 'leads.create'],
            '/career-consultations' => ['consultations.view'],
            '/applied-jobs' => ['leads.view', 'applications.view'],
        ];

        foreach ($blocked as $prefix => $permissions) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                foreach ($permissions as $permission) {
                    if ($resolver->can($admin, $permission)) {
                        return true;
                    }
                }

                return false;
            }
        }

        return true;
    }

    public static function pipelineRouteNameFor(Admin $admin): string
    {
        $team = app(SalesTeamService::class)->teamFor($admin);

        return match ($team) {
            SalesTeam::Employer => 'admin.employers.pipeline.index',
            SalesTeam::Candidate => 'admin.leads.index',
            default => 'admin.leads.index',
        };
    }
}
