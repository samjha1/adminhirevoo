<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_company_outreach_leads', function (Blueprint $table) {
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('admins')->nullOnDelete();
            $table->string('assignment_role_level', 32)->nullable()->after('sales_manager_id');
            $table->index(['assignment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_company_outreach_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn('assignment_role_level');
            $table->dropIndex(['assignment_status']);
        });
    }
};
