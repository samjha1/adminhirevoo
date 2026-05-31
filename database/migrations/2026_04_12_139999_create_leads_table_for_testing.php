<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal leads table for sqlite tests / local dev when Hirevo schema is absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            return;
        }

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedBigInteger('employer_job_id')->nullable();
            $table->unsignedBigInteger('upskill_opportunity_id')->nullable();
            $table->string('status', 32)->default('available');
            $table->unsignedTinyInteger('match_percentage')->nullable();
            $table->unsignedTinyInteger('intent_score')->nullable();
            $table->json('missing_skills')->nullable();
            $table->string('referral_source', 100)->nullable();
            $table->string('lead_summary', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('leads');
    }
};
