<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['crm_call_logs', 'crm_follow_ups', 'crm_lead_activities', 'crm_lead_notes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'employer_prospect_id')) {
                    $blueprint->unsignedBigInteger('employer_prospect_id')->nullable()->after('lead_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['crm_call_logs', 'crm_follow_ups', 'crm_lead_activities', 'crm_lead_notes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'employer_prospect_id')) {
                    $blueprint->dropColumn('employer_prospect_id');
                }
            });
        }
    }
};
