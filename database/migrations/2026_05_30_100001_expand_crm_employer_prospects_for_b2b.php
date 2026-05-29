<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_employer_prospects', function (Blueprint $table) {
            $table->string('industry', 128)->nullable()->after('company_name');
            $table->string('website')->nullable()->after('industry');
            $table->string('company_size', 64)->nullable()->after('website');
            $table->string('location', 128)->nullable()->after('company_size');
            $table->string('contact_designation', 128)->nullable()->after('contact_name');
            $table->string('linkedin_url')->nullable()->after('email');
            $table->string('pipeline_stage', 64)->default('lead_generated')->after('sales_status');
            $table->timestamp('follow_up_at')->nullable()->after('pipeline_stage');
            $table->timestamp('last_activity_at')->nullable()->after('follow_up_at');
            $table->decimal('deal_value', 12, 2)->nullable()->after('last_activity_at');
            $table->unsignedTinyInteger('win_probability')->nullable()->after('deal_value');
            $table->decimal('expected_revenue', 12, 2)->nullable()->after('win_probability');
            $table->string('proposal_status', 32)->nullable()->after('expected_revenue');
            $table->string('call_recording_url')->nullable()->after('notes');
        });

        if (Schema::hasColumn('crm_employer_prospects', 'crm_stage')) {
            DB::table('crm_employer_prospects')->where('crm_stage', 'new')->update(['pipeline_stage' => 'lead_generated']);
        }
    }

    public function down(): void
    {
        Schema::table('crm_employer_prospects', function (Blueprint $table) {
            $table->dropColumn([
                'industry', 'website', 'company_size', 'location', 'contact_designation',
                'linkedin_url', 'pipeline_stage', 'follow_up_at', 'last_activity_at',
                'deal_value', 'win_probability', 'expected_revenue', 'proposal_status',
                'call_recording_url',
            ]);
        });
    }
};
