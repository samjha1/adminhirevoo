<?php

namespace App\Enums;

enum SalesTeam: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Talent (Candidates)',
            self::Employer => 'Companies (Employers)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Candidate => 'Talent team',
            self::Employer => 'Company team',
        };
    }

    public function pipelineTitle(): string
    {
        return match ($this) {
            self::Candidate => 'Talent pipeline',
            self::Employer => 'Company pipeline',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Candidate => 'bi-person-workspace',
            self::Employer => 'bi-buildings',
        };
    }

    /** Normalize enum, string, or null to a team slug (defaults to talent when unknown). */
    public static function normalize(mixed $team, self $default = self::Candidate): string
    {
        if ($team instanceof self) {
            return $team->value;
        }

        if (is_string($team) && $team !== '') {
            return self::tryFrom($team)?->value ?? $default->value;
        }

        return $default->value;
    }
}
