<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_company_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_prospect_id')->constrained('crm_employer_prospects')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('meeting_at');
            $table->string('meeting_type', 64)->default('discovery');
            $table->string('outcome', 64)->nullable();
            $table->text('attendees')->nullable();
            $table->text('notes')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_company_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_prospect_id')->constrained('crm_employer_prospects')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('sent_at')->nullable();
            $table->string('package_offered')->nullable();
            $table->decimal('package_value', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('expected_revenue', 12, 2)->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
        });

        Schema::create('crm_company_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_prospect_id')->unique()->constrained('crm_employer_prospects')->cascadeOnDelete();
            $table->foreignId('account_manager_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('package_purchased')->nullable();
            $table->date('start_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->unsignedInteger('active_positions')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_company_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_prospect_id')->constrained('crm_employer_prospects')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type', 64);
            $table->string('title');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['employer_prospect_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_company_activities');
        Schema::dropIfExists('crm_company_clients');
        Schema::dropIfExists('crm_company_proposals');
        Schema::dropIfExists('crm_company_meetings');
    }
};
