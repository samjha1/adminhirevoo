<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_follow_ups')) {
            return;
        }

        Schema::table('crm_follow_ups', function (Blueprint $table) {
            if (Schema::hasColumn('crm_follow_ups', 'lead_id')) {
                $table->unsignedBigInteger('lead_id')->nullable()->change();
            }
            if (! Schema::hasColumn('crm_follow_ups', 'employer_prospect_id')) {
                $table->unsignedBigInteger('employer_prospect_id')->nullable()->after('lead_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_follow_ups')) {
            return;
        }

        Schema::table('crm_follow_ups', function (Blueprint $table) {
            if (Schema::hasColumn('crm_follow_ups', 'employer_prospect_id')) {
                $table->dropColumn('employer_prospect_id');
            }
        });
    }
};
