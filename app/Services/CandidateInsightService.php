<?php

namespace App\Services;

use App\Models\Hirevo\HirevoLead;

class CandidateInsightService
{
    public function buildLeadInsight(HirevoLead $lead): array
    {
        $candidate = $lead->candidate;
        $profile = $candidate?->candidateProfile;
        $resume = $candidate?->resumes?->first();

        $match = (int) ($lead->match_percentage ?? 0);
        $intent = (int) ($lead->intent_score ?? 0);
        $summaryParts = [];

        $summaryParts[] = ($candidate?->name ?? 'Candidate').' is currently in lead stage "'.$lead->status.'".';
        $summaryParts[] = "Match score is {$match}% with intent score {$intent}.";

        if ($profile?->experience_years !== null) {
            $summaryParts[] = "Experience is approximately {$profile->experience_years} years.";
        }
        if ($profile?->preferred_job_role) {
            $summaryParts[] = "Preferred role appears to be {$profile->preferred_job_role}.";
        }
        if ($profile?->preferred_job_location) {
            $summaryParts[] = "Preferred location is {$profile->preferred_job_location}.";
        }
        if ($resume?->ai_summary) {
            $summaryParts[] = 'Resume summary indicates: '.trim($resume->ai_summary);
        }

        $missingSkills = is_array($lead->missing_skills) ? array_values(array_filter($lead->missing_skills)) : [];
        $topMissing = array_slice($missingSkills, 0, 5);

        $interestLevel = 'low';
        if ($match >= 70 && $intent >= 50) {
            $interestLevel = 'high';
        } elseif ($match >= 50 || $intent >= 35) {
            $interestLevel = 'medium';
        }

        $upskillRecommendations = [];
        foreach ($topMissing as $skill) {
            $upskillRecommendations[] = "Upskill on {$skill} via short practical project + interview prep.";
        }
        if (empty($upskillRecommendations)) {
            $upskillRecommendations[] = 'Continue interview preparation and strengthen project portfolio.';
        }

        $nextBestActions = match ($interestLevel) {
            'high' => [
                'Call within 24 hours and qualify availability.',
                'Push for interview pipeline in matching roles.',
                'Share targeted resume refinement notes.',
            ],
            'medium' => [
                'Call and identify blockers before referral.',
                'Enroll in focused upskill track for 1-2 missing skills.',
                'Re-evaluate after profile update.',
            ],
            default => [
                'Mark as nurture lead and follow up weekly.',
                'Prioritize upskill completion before referrals.',
                'Run profile completeness checklist.',
            ],
        };

        return [
            'interest_level' => $interestLevel,
            'executive_summary' => implode(' ', $summaryParts),
            'missing_skills' => $topMissing,
            'upskill_recommendations' => $upskillRecommendations,
            'next_best_actions' => $nextBestActions,
        ];
    }
}

