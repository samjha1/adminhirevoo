<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;

class SalesTeamService
{
    public function teamFor(Admin $admin): ?SalesTeam
    {
        if ($admin->role?->hasUnrestrictedLeadVisibility()) {
            return null;
        }

        if ($admin->sales_team instanceof SalesTeam) {
            return $admin->sales_team;
        }

        if (is_string($admin->sales_team) && $admin->sales_team !== '') {
            return SalesTeam::tryFrom($admin->sales_team);
        }

        return $admin->hasAnyRole([AdminRole::SalesManager, AdminRole::SalesEmployee])
            ? SalesTeam::Candidate
            : null;
    }

    public function canAccessPipeline(Admin $admin, SalesTeam $pipeline): bool
    {
        $team = $this->teamFor($admin);

        return $team === null || $team === $pipeline;
    }

    /** @param  Builder<Admin>  $query */
    public function scopeSalesStaff(Builder $query, SalesTeam $team, AdminRole $role): Builder
    {
        return $query
            ->where('role', $role)
            ->where('sales_team', $team->value);
    }

    public function assertSameTeam(Admin $actor, Admin $target): void
    {
        $actorTeam = $this->teamFor($actor);
        $targetTeam = $this->teamFor($target);

        if ($actorTeam === null || $targetTeam === null) {
            return;
        }

        if ($actorTeam !== $targetTeam) {
            throw new \InvalidArgumentException(
                "Cannot assign across teams. Target is on {$targetTeam->shortLabel()}, you are on {$actorTeam->shortLabel()}."
            );
        }
    }
}
