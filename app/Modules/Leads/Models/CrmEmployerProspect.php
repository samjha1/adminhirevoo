<?php

namespace App\Modules\Leads\Models;

use App\Enums\AssignmentRoleLevel;
use App\Enums\CompanyB2bPipelineStage;
use App\Enums\LeadAssignmentStatus;
use App\Enums\LeadSalesStatus;
use App\Models\Admin;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CrmEmployerProspect extends Model
{
    protected $table = 'crm_employer_prospects';

    protected $fillable = [
        'user_id', 'company_name', 'industry', 'website', 'company_size', 'location',
        'contact_name', 'contact_designation', 'phone', 'email', 'linkedin_url',
        'source', 'notes', 'call_recording_url', 'created_by',
        'assigned_to', 'assigned_by', 'sales_manager_id',
        'assignment_role_level', 'assignment_status', 'sales_status',
        'crm_stage', 'pipeline_stage', 'follow_up_at', 'last_activity_at',
        'deal_value', 'win_probability', 'expected_revenue', 'proposal_status',
    ];

    protected function casts(): array
    {
        return [
            'assignment_role_level' => AssignmentRoleLevel::class,
            'assignment_status' => LeadAssignmentStatus::class,
            'sales_status' => LeadSalesStatus::class,
            'follow_up_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'deal_value' => 'decimal:2',
            'expected_revenue' => 'decimal:2',
        ];
    }

    public function hirevoUser(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sales_manager_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(CrmCompanyMeeting::class, 'employer_prospect_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(CrmCompanyProposal::class, 'employer_prospect_id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(CrmCompanyClient::class, 'employer_prospect_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmCompanyActivity::class, 'employer_prospect_id')->orderByDesc('created_at');
    }

    public function pipelineStageEnum(): CompanyB2bPipelineStage
    {
        if ($this->pipeline_stage instanceof CompanyB2bPipelineStage) {
            return $this->pipeline_stage;
        }

        return CompanyB2bPipelineStage::tryFrom((string) $this->pipeline_stage)
            ?? CompanyB2bPipelineStage::LeadGenerated;
    }
}
