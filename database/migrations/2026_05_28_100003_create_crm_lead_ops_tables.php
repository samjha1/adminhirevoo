<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('outcome', 32);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('crm_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['lead_id', 'created_at']);
            $table->index(['admin_id', 'scheduled_at']);
        });

        Schema::create('crm_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type', 64);
            $table->string('title');
            $table->json('payload')->nullable();
            $table->nullableMorphs('source');
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('crm_lead_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_notes');
        Schema::dropIfExists('crm_lead_activities');
        Schema::dropIfExists('crm_follow_ups');
        Schema::dropIfExists('crm_call_logs');
    }
};
