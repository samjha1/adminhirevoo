<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (! $this->hasIndex('leads', 'leads_assigned_to_index')) {
                    $table->index('assigned_to');
                }
                if (! $this->hasIndex('leads', 'leads_sales_manager_id_index')) {
                    $table->index('sales_manager_id');
                }
            });
        }

        if (Schema::hasTable('crm_employer_prospects')) {
            Schema::table('crm_employer_prospects', function (Blueprint $table) {
                if (! $this->hasIndex('crm_employer_prospects', 'crm_employer_prospects_assigned_to_index')) {
                    $table->index('assigned_to');
                }
                if (! $this->hasIndex('crm_employer_prospects', 'crm_employer_prospects_sales_manager_id_index')) {
                    $table->index('sales_manager_id');
                }
            });
        }

        if (Schema::hasTable('crm_call_logs')) {
            Schema::table('crm_call_logs', function (Blueprint $table) {
                if (! $this->hasIndex('crm_call_logs', 'crm_call_logs_called_at_index')) {
                    $table->index('called_at');
                }
            });
        }

        if (Schema::hasTable('crm_company_meetings')) {
            Schema::table('crm_company_meetings', function (Blueprint $table) {
                if (! $this->hasIndex('crm_company_meetings', 'crm_company_meetings_meeting_at_index')) {
                    $table->index('meeting_at');
                }
            });
        }
    }

    public function down(): void
    {
        // Indexes are safe to leave; optional drop omitted for shared DB compatibility.
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
        } catch (\Throwable) {
            return false;
        }

        foreach ($indexes as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
};
