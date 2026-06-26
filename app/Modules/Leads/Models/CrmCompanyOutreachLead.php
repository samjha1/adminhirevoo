<?php

namespace App\Modules\Leads\Models;

use App\Enums\AssignmentRoleLevel;
use App\Enums\CompanyOutreachStage;
use App\Enums\LeadAssignmentStatus;
use App\Enums\LeadSalesStatus;
use App\Models\Admin;
use App\Models\Hirevo\HirevoUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCompanyOutreachLead extends Model
{
    protected $table = 'crm_company_outreach_leads';

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'email',
        'industry',
        'website',
        'location',
        'source',
        'notes',
        'outreach_stage',
        'user_id',
        'created_by',
        'assigned_to',
        'assigned_by',
        'sales_manager_id',
        'assignment_role_level',
        'assignment_status',
        'sales_status',
        'follow_up_at',
        'last_call_at',
    ];

    protected function casts(): array
    {
        return [
            'assignment_role_level' => AssignmentRoleLevel::class,
            'assignment_status' => LeadAssignmentStatus::class,
            'sales_status' => LeadSalesStatus::class,
            'follow_up_at' => 'datetime',
            'last_call_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
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

    public function hirevoUser(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'user_id');
    }

    public function outreachStageEnum(): CompanyOutreachStage
    {
        return CompanyOutreachStage::tryFrom((string) $this->outreach_stage)
            ?? CompanyOutreachStage::New;
    }
}
