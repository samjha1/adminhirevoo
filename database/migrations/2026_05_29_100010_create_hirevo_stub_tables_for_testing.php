<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing') && Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('role', 32)->default('candidate');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referrer_profiles')) {
            Schema::create('referrer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('company_name');
                $table->string('company_email')->nullable();
                $table->string('referral_code', 50)->nullable();
                $table->boolean('is_approved')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('career_consultation_requests')) {
            Schema::create('career_consultation_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('status', 32)->default('pending');
                $table->string('source')->nullable();
                $table->unsignedTinyInteger('match_percentage')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('career_consultation_requests');
        Schema::dropIfExists('referrer_profiles');
        Schema::dropIfExists('users');
    }
};
