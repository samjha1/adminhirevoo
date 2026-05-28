<?php

namespace App\Services;

class LeadService
{
    public function computeLeadScore(?int $profileScore, ?int $activityScore): int
    {
        return max(0, (int) $profileScore) + max(0, (int) $activityScore);
    }
}

