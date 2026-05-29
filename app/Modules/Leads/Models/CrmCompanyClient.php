<?php

namespace App\Modules\Leads\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCompanyClient extends Model
{
    protected $table = 'crm_company_clients';

    protected $fillable = [
        'employer_prospect_id', 'account_manager_id', 'package_purchased',
        'start_date', 'renewal_date', 'active_positions',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'renewal_date' => 'date',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(CrmEmployerProspect::class, 'employer_prospect_id');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'account_manager_id');
    }
}
