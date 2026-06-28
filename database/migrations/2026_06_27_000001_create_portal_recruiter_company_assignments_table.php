<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_recruiter_company_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->unsignedBigInteger('employer_user_id');
            $table->foreignId('assigned_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['admin_id', 'employer_user_id'], 'prca_admin_employer_unique');
            $table->index('employer_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_recruiter_company_assignments');
    }
};
