<?php

namespace App\Models;

use App\Models\Hirevo\HirevoUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalRecruiterCompanyAssignment extends Model
{
    protected $fillable = [
        'admin_id',
        'employer_user_id',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(HirevoUser::class, 'employer_user_id');
    }
}
