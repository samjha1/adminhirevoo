<?php

namespace App\Support;

use App\Enums\SalesTeam;
use App\Models\Admin;
use App\Services\SalesTeamService;

final class AdminHomeResolver
{
    public static function routeNameFor(Admin $admin): string
    {
        $team = app(SalesTeamService::class)->teamFor($admin);

        return 'admin.dashboard';
    }

    public static function urlFor(Admin $admin): string
    {
        return route(self::routeNameFor($admin));
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
