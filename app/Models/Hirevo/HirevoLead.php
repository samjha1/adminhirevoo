<?php

namespace App\Models\Hirevo;

use App\Enums\AssignmentRoleLevel;
use App\Enums\LeadAssignmentStatus;
use App\Enums\LeadSalesStatus;
use App\Models\Admin;
use App\Models\AdminLeadStage;
use App\Models\LeadAssignmentHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HirevoLead extends Model
{
    protected $table = 'leads';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'missing_skills' => 'array',
            'assignment_role_level' => AssignmentRoleLevel::class,
            'assignment_status' => LeadAssignmentStatus::class,
            'sales_status' => LeadSalesStatus::class,
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'candidate_id');
    }

    public function adminStage(): HasOne
    {
        return $this->hasOne(AdminLeadStage::class, 'lead_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sales_manager_id');
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(LeadAssignmentHistory::class, 'lead_id')->orderByDesc('created_at');
    }

    public function employerJob(): BelongsTo
    {
        return $this->belongsTo(HirevoEmployerJob::class, 'employer_job_id');
    }

    public function jobRole(): BelongsTo
    {
        return $this->belongsTo(HirevoJobRole::class, 'job_role_id');
    }
}
