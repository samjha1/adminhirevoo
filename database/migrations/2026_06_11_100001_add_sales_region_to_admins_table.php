<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('sales_region', 16)->nullable()->after('sales_team');
            $table->index('sales_region');
            $table->index(['sales_team', 'sales_region', 'role'], 'admins_team_region_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndex('admins_team_region_role_idx');
            $table->dropIndex(['sales_region']);
            $table->dropColumn('sales_region');
        });
    }
};
