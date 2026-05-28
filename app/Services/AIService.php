<?php

namespace App\Services;

class AIService
{
    public function estimateMatchScore(array $candidateSkills, array $jobSkills): int
    {
        if (empty($jobSkills)) {
            return 0;
        }

        $candidate = array_map('strtolower', $candidateSkills);
        $job = array_map('strtolower', $jobSkills);
        $matched = count(array_intersect($candidate, $job));

        return (int) round(($matched / count($job)) * 100);
    }
}

