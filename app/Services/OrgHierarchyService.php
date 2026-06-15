<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrgHierarchyService
{
    /** @return Collection<int, int> */
    public function descendantIds(Admin $root): Collection
    {
        $all = collect([$root->id]);
        $frontier = [$root->id];

        while ($frontier !== []) {
            $children = Admin::query()
                ->whereIn('manager_id', $frontier)
                ->pluck('id');

            if ($children->isEmpty()) {
                break;
            }

            $all = $all->merge($children);
            $frontier = $children->all();
        }

        return $all->unique()->values();
    }

    public function inheritRegion(?int $managerId): ?SalesRegion
    {
        if ($managerId === null) {
            return null;
        }

        $manager = Admin::query()->find($managerId);

        while ($manager !== null) {
            if ($manager->sales_region instanceof SalesRegion) {
                return $manager->sales_region;
            }

            if (is_string($manager->sales_region) && $manager->sales_region !== '') {
                $region = SalesRegion::tryFrom($manager->sales_region);
                if ($region !== null) {
                    return $region;
                }
            }

            if ($manager->manager_id === null) {
                break;
            }

            $manager = $manager->manager;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function validateAssignment(
        AdminRole $role,
        ?SalesTeam $team,
        ?SalesRegion $region,
        ?int $managerId,
        ?int $excludeAdminId = null,
    ): array {
        $errors = [];

        if ($role->isSalesFieldRole() && $team === null) {
            $errors['sales_team'] = 'Choose Talent or Company team for sales roles.';
        }

        if ($role === AdminRole::Asm) {
            if ($region === null) {
                $errors['sales_region'] = 'Choose North or South region for ASM roles.';
            }

            if ($managerId === null) {
                $errors['manager_id'] = 'ASM must report to an Admin or Super Admin.';
            } else {
                $manager = Admin::query()->find($managerId);
                if ($manager === null) {
                    $errors['manager_id'] = 'Selected manager does not exist.';
                } elseif (! $manager->role?->isPlatformAdmin()) {
                    $errors['manager_id'] = 'ASM must report to an Admin or Super Admin.';
                }
            }

            if ($team !== null && $region !== null && $this->asmExistsForTeamRegion($team, $region, $excludeAdminId)) {
                $errors['sales_region'] = 'An ASM already exists for this team and region.';
            }
        }

        if ($role === AdminRole::SalesManager) {
            if ($managerId === null) {
                $errors['manager_id'] = 'Sales managers must report to an ASM.';
            } else {
                $manager = Admin::query()->find($managerId);
                if ($manager === null) {
                    $errors['manager_id'] = 'Selected manager does not exist.';
                } elseif ($manager->role !== AdminRole::Asm) {
                    $errors['manager_id'] = 'Sales managers must report to an ASM.';
                } elseif ($team !== null) {
                    $managerTeam = SalesTeam::normalize($manager->sales_team);
                    if ($managerTeam !== $team->value) {
                        $errors['manager_id'] = 'Selected ASM is on a different team. Choose an ASM from the '.$team->shortLabel().'.';
                    }
                }
            }
        }

        if ($role === AdminRole::SalesEmployee) {
            if ($managerId === null) {
                $errors['manager_id'] = 'Sales employees must report to a sales manager.';
            } else {
                $manager = Admin::query()->find($managerId);
                if ($manager === null) {
                    $errors['manager_id'] = 'Selected manager does not exist.';
                } elseif ($manager->role !== AdminRole::SalesManager) {
                    $errors['manager_id'] = 'Sales employees must report to a sales manager.';
                } elseif ($team !== null) {
                    $managerTeam = SalesTeam::normalize($manager->sales_team);
                    if ($managerTeam !== $team->value) {
                        $errors['manager_id'] = 'Selected manager is on a different team. Choose a manager from the '.$team->shortLabel().'.';
                    }
                }
            }
        }

        if ($managerId && $team && in_array($role, [AdminRole::SalesManager, AdminRole::SalesEmployee], true)) {
            $manager = Admin::query()->find($managerId);
            if ($manager && $role === AdminRole::SalesManager && $manager->role === AdminRole::Asm) {
                $managerRegion = SalesRegion::normalize($manager->sales_region);
                $expectedRegion = $region?->value ?? $this->inheritRegion($managerId)?->value;
                if ($managerRegion !== null && $expectedRegion !== null && $managerRegion !== $expectedRegion) {
                    $errors['manager_id'] = 'Selected ASM region does not match.';
                }
            }
        }

        return $errors;
    }

    public function resolveRegionForRole(AdminRole $role, ?SalesRegion $region, ?int $managerId): ?SalesRegion
    {
        if ($role === AdminRole::Asm) {
            return $region;
        }

        if ($role->isSalesFieldRole()) {
            return $this->inheritRegion($managerId);
        }

        return null;
    }

    /** @return Collection<int, Admin> */
    public function eligibleManagers(AdminRole $forRole, ?SalesTeam $team, ?SalesRegion $region): Collection
    {
        $requiredRole = $forRole->requiredManagerRole();

        if ($requiredRole === null) {
            return collect();
        }

        $query = Admin::query()->orderBy('name');

        if ($forRole === AdminRole::Asm) {
            return $query
                ->whereIn('role', [AdminRole::Admin, AdminRole::SuperAdmin])
                ->get();
        }

        $query->where('role', $requiredRole);

        if ($team !== null) {
            $query->where('sales_team', $team->value);
        }

        if ($region !== null && $requiredRole === AdminRole::Asm) {
            $query->where('sales_region', $region->value);
        }

        return $query->get();
    }

    /** @return list<AdminRole> */
    public function rolesCreatableBy(Admin $actor): array
    {
        if ($actor->role?->isPlatformAdmin() || $actor->role === AdminRole::SuperAdmin) {
            return AdminRole::cases();
        }

        if ($actor->role === AdminRole::Asm) {
            return [AdminRole::SalesManager];
        }

        if ($actor->role === AdminRole::SalesManager) {
            return [AdminRole::SalesEmployee];
        }

        return [];
    }

    /** @param  Builder<Admin>  $query */
    public function scopeManageableStaff(Builder $query, Admin $actor): void
    {
        if ($actor->role?->isPlatformAdmin() || $actor->role === AdminRole::SuperAdmin) {
            return;
        }

        if ($actor->role === AdminRole::Asm) {
            $ids = $this->descendantIds($actor);
            $query->whereIn('id', $ids->reject(fn (int $id) => $id === $actor->id));

            return;
        }

        if ($actor->role === AdminRole::SalesManager) {
            $query->where('role', AdminRole::SalesEmployee)
                ->where('manager_id', $actor->id);

            return;
        }

        $query->whereRaw('0 = 1');
    }

    public function assertActorCanManage(Admin $actor, Admin $target): void
    {
        if ($actor->role?->isPlatformAdmin() || $actor->role === AdminRole::SuperAdmin) {
            return;
        }

        if ($actor->role === AdminRole::Asm) {
            $manageable = $this->descendantIds($actor)->contains($target->id);
            abort_unless($manageable, 403, 'You can only manage staff in your region.');

            if ($target->role === AdminRole::SalesEmployee) {
                abort_unless(
                    (int) $target->manager?->manager_id === (int) $actor->id
                        || $this->descendantIds($actor)->contains($target->id),
                    403,
                    'You can only view employees in your region.'
                );
            }

            return;
        }

        if ($actor->role === AdminRole::SalesManager) {
            abort_unless(
                $target->role === AdminRole::SalesEmployee && (int) $target->manager_id === (int) $actor->id,
                403,
                'You can only manage sales employees on your team.'
            );

            return;
        }

        abort(403);
    }

    public function actorCanEditStaff(Admin $actor, Admin $target): bool
    {
        if ($actor->role?->isPlatformAdmin() || $actor->role === AdminRole::SuperAdmin) {
            return true;
        }

        if ($actor->role === AdminRole::Asm) {
            return $target->role === AdminRole::SalesManager
                && (int) $target->manager_id === (int) $actor->id;
        }

        if ($actor->role === AdminRole::SalesManager) {
            return $target->role === AdminRole::SalesEmployee
                && (int) $target->manager_id === (int) $actor->id;
        }

        return false;
    }

    public function actorCreatesEmployeesOnly(Admin $actor): bool
    {
        return $actor->role === AdminRole::SalesManager;
    }

    public function actorCreatesManagersOnly(Admin $actor): bool
    {
        return $actor->role === AdminRole::Asm;
    }

    private function asmExistsForTeamRegion(SalesTeam $team, SalesRegion $region, ?int $excludeAdminId = null): bool
    {
        $query = Admin::query()
            ->where('role', AdminRole::Asm)
            ->where('sales_team', $team->value)
            ->where('sales_region', $region->value);

        if ($excludeAdminId !== null) {
            $query->where('id', '!=', $excludeAdminId);
        }

        return $query->exists();
    }
}
