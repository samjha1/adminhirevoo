<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins') && ! Schema::hasColumn('admins', 'referral_code')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('referral_code', 50)->nullable()->unique()->after('email');
            });
        }

        if (Schema::hasTable('referrer_profiles') && ! Schema::hasColumn('referrer_profiles', 'referral_code')) {
            Schema::table('referrer_profiles', function (Blueprint $table) {
                $table->string('referral_code', 50)->nullable()->after('company_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'referral_code')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropUnique(['referral_code']);
                $table->dropColumn('referral_code');
            });
        }
    }
};
