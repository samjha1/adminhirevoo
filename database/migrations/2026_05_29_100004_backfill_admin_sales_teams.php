<?php

use App\Enums\AdminRole;
use App\Enums\SalesTeam;
use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Admin::query()
            ->whereIn('role', [
                AdminRole::SalesManager->value,
                AdminRole::SalesEmployee->value,
            ])
            ->whereNull('sales_team')
            ->update(['sales_team' => SalesTeam::Candidate->value]);
    }

    public function down(): void
    {
        //
    }
};
