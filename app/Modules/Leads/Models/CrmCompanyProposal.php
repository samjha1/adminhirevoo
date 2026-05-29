<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCompanyProposal extends Model
{
    protected $table = 'crm_company_proposals';

    protected $fillable = [
        'employer_prospect_id', 'admin_id', 'sent_at', 'package_offered',
        'package_value', 'discount_percent', 'expected_revenue', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'date',
            'package_value' => 'decimal:2',
            'expected_revenue' => 'decimal:2',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(CrmEmployerProspect::class, 'employer_prospect_id');
    }
}
