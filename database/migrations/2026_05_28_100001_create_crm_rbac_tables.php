<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 128)->unique();
            $table->string('group', 64)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_role_id')->constrained('crm_roles')->cascadeOnDelete();
            $table->foreignId('crm_permission_id')->constrained('crm_permissions')->cascadeOnDelete();
            $table->unique(['crm_role_id', 'crm_permission_id']);
        });

        Schema::create('crm_admin_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('crm_role_id')->constrained('crm_roles')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->unique(['admin_id', 'crm_role_id']);
        });

        Schema::create('crm_admin_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('crm_permission_id')->constrained('crm_permissions')->cascadeOnDelete();
            $table->enum('effect', ['grant', 'deny']);
            $table->timestamps();
            $table->unique(['admin_id', 'crm_permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_admin_permission_overrides');
        Schema::dropIfExists('crm_admin_roles');
        Schema::dropIfExists('crm_role_permissions');
        Schema::dropIfExists('crm_permissions');
        Schema::dropIfExists('crm_roles');
    }
};
