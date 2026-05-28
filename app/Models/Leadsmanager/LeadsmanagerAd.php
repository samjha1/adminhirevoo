<?php

namespace App\Models\Leadsmanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadsmanagerAd extends Model
{
    protected $table = 'leadsmanager_ads';

    protected $guarded = [];

    public const PLACEMENTS = [
        'hirevo_homepage' => 'Hirevo — Homepage',
        'hirevo_jobs' => 'Hirevo — Job listings & goals',
        'hirevo_dashboard' => 'Hirevo — Candidate dashboard',
        'hirevo_sidebar' => 'Hirevo — Sidebar / skill pages',
        'hirevo_email' => 'Hirevo — Email (future)',
    ];

    public const STATUSES = ['draft', 'pending_review', 'active', 'paused', 'archived'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerCampaign::class, 'campaign_id');
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(LeadsmanagerAdvertiser::class, 'user_id');
    }

    public function placementLabel(): string
    {
        return self::PLACEMENTS[$this->placement] ?? ucfirst(str_replace('_', ' ', (string) $this->placement));
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'pending_review' => 'warning',
            'paused' => 'secondary',
            'archived' => 'dark',
            default => 'light',
        };
    }
}
