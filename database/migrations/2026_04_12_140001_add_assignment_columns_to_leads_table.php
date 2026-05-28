<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('updated_at')
                ->constrained('admins')
                ->nullOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('admins')
                ->nullOnDelete();
            $table->foreignId('sales_manager_id')
                ->nullable()
                ->after('assigned_by')
                ->constrained('admins')
                ->nullOnDelete();

            $table->string('assignment_role_level', 32)->nullable()->after('sales_manager_id');
            $table->string('assignment_status', 32)->default('new')->after('assignment_role_level');
            $table->string('sales_status', 32)->default('pending')->after('assignment_status');

            $table->index(['assigned_to']);
            $table->index(['sales_manager_id']);
            $table->index(['assignment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['assigned_by']);
            $table->dropForeign(['sales_manager_id']);
            $table->dropColumn([
                'assigned_to',
                'assigned_by',
                'sales_manager_id',
                'assignment_role_level',
                'assignment_status',
                'sales_status',
            ]);
        });
    }
};
