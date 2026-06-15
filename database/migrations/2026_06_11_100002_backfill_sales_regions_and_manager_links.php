<?php

use App\Enums\AdminRole;
use App\Enums\SalesRegion;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $platformAdmin = Admin::query()
            ->where('role', AdminRole::Admin)
            ->orderBy('id')
            ->first();

        if ($platformAdmin === null) {
            return;
        }

        $defaults = [
            [SalesTeam::Candidate, SalesRegion::North],
            [SalesTeam::Candidate, SalesRegion::South],
            [SalesTeam::Employer, SalesRegion::North],
            [SalesTeam::Employer, SalesRegion::South],
        ];

        foreach ($defaults as [$team, $region]) {
            $asm = Admin::query()->firstOrCreate(
                [
                    'role' => AdminRole::Asm,
                    'sales_team' => $team->value,
                    'sales_region' => $region->value,
                ],
                [
                    'name' => 'ASM '.$team->shortLabel().' '.$region->label().' (Auto)',
                    'email' => 'asm.'.$team->value.'.'.$region->value.'.'.uniqid().'@placeholder.local',
                    'password' => bcrypt(str()->random(32)),
                    'manager_id' => $platformAdmin->id,
                ],
            );

            Admin::query()
                ->where('role', AdminRole::SalesManager)
                ->where('sales_team', $team->value)
                ->whereNull('manager_id')
                ->where(function ($q) use ($region) {
                    $q->where('sales_region', $region->value)
                        ->orWhereNull('sales_region');
                })
                ->update([
                    'manager_id' => $asm->id,
                    'sales_region' => $region->value,
                ]);

            $managerIds = Admin::query()
                ->where('sales_region', $region->value)
                ->where('role', AdminRole::SalesManager)
                ->pluck('id');

            if ($managerIds->isNotEmpty()) {
                Admin::query()
                    ->where('role', AdminRole::SalesEmployee)
                    ->where('sales_team', $team->value)
                    ->whereNull('sales_region')
                    ->whereIn('manager_id', $managerIds)
                    ->update(['sales_region' => $region->value]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive backfill — no rollback.
    }
};
