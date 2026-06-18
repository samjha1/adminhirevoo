<?php

namespace App\Models\Leadsmanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadsmanagerAd extends Model
{
    protected $table = 'leadsmanager_ads';

    protected $guarded = [];

    public const PLACEMENTS = [
        'feed' => 'News Feed',
        'story' => 'Stories',
        'sidebar' => 'Sidebar Banner',
        'search' => 'Search Results',
        'email' => 'Email Newsletter',
        'hirevo_homepage' => 'Hirevo — Homepage',
        'hirevo_jobs' => 'Hirevo — Job listings',
        'hirevo_dashboard' => 'Hirevo — Candidate dashboard',
        'hirevo_sidebar' => 'Hirevo — Sidebar',
        'hirevo_email' => 'Hirevo — Email',
    ];

    public const STATUSES = [
        'draft',
        'under_review',
        'pending_review',
        'approved',
        'rejected',
        'active',
        'paused',
        'completed',
        'archived',
    ];

    public const REVIEW_STATUSES = ['under_review', 'pending_review'];

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
            'approved' => 'info',
            'under_review', 'pending_review' => 'warning',
            'rejected' => 'danger',
            'paused' => 'secondary',
            'completed', 'archived' => 'dark',
            default => 'light',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'under_review', 'pending_review' => 'Under review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'active' => 'Live on Hirevo',
            'paused' => 'Paused',
            'draft' => 'Draft',
            'completed' => 'Completed',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function isPendingReview(): bool
    {
        return in_array($this->status, self::REVIEW_STATUSES, true);
    }

    public function displayImageUrl(): ?string
    {
        if (! filled($this->image_path) && ! filled($this->image_url)) {
            return null;
        }

        $base = rtrim((string) config('leadsmanager.api_base_url'), '/');
        if ($base !== '' && filled($this->public_key)) {
            return "{$base}/api/ads/image/{$this->public_key}";
        }

        return $this->image_url;
    }
}
