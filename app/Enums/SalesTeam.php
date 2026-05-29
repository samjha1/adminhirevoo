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
}
