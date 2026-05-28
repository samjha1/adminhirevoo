<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLeadStage extends Model
{
    protected $table = 'admin_lead_stages';

    protected $fillable = [
        'lead_id',
        'stage',
        'notes',
        'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'last_contacted_at' => 'datetime',
        ];
    }
}

