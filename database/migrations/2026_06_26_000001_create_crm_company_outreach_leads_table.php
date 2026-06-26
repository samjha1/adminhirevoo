<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_company_outreach_leads', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('industry', 128)->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('source', 64)->nullable();
            $table->text('notes')->nullable();
            $table->string('outreach_stage', 32)->default('new');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('sales_manager_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('assignment_status', 32)->default('new');
            $table->string('sales_status', 32)->default('pending');
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('last_call_at')->nullable();
            $table->timestamps();
            $table->index(['outreach_stage']);
            $table->index(['sales_manager_id']);
            $table->index(['assigned_to']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_company_outreach_leads');
    }
};
