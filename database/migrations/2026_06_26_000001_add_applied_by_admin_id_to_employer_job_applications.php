<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employer_job_applications')) {
            return;
        }

        if (Schema::hasColumn('employer_job_applications', 'applied_by_admin_id')) {
            return;
        }

        Schema::table('employer_job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('applied_by_admin_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employer_job_applications')) {
            return;
        }

        if (! Schema::hasColumn('employer_job_applications', 'applied_by_admin_id')) {
            return;
        }

        Schema::table('employer_job_applications', function (Blueprint $table) {
            $table->dropColumn('applied_by_admin_id');
        });
    }
};
