<?php

namespace App\Enums;

enum CompanyB2bPipelineStage: string
{
    case LeadGenerated = 'lead_generated';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case MeetingScheduled = 'meeting_scheduled';
    case DemoCompleted = 'demo_completed';
    case ProposalSent = 'proposal_sent';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Onboarding = 'onboarding';
    case HiringActive = 'hiring_active';
    case Renewed = 'renewed';
    case Lost = 'lost';

  /** @return list<self> */
    public static function ordered(): array
    {
        return [
            self::LeadGenerated,
            self::Contacted,
            self::Interested,
            self::MeetingScheduled,
            self::DemoCompleted,
            self::ProposalSent,
            self::Negotiation,
            self::Won,
            self::Onboarding,
            self::HiringActive,
            self::Renewed,
        ];
    }

    /** Active pipeline stages (excludes terminal lost). @return list<self> */
    public static function activePipeline(): array
    {
        return self::ordered();
    }

    public function label(): string
    {
        return match ($this) {
            self::LeadGenerated => 'Lead generated',
            self::Contacted => 'Contacted',
            self::Interested => 'Interested',
            self::MeetingScheduled => 'Meeting scheduled',
            self::DemoCompleted => 'Demo completed',
            self::ProposalSent => 'Proposal sent',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Onboarding => 'Onboarding',
            self::HiringActive => 'Hiring active',
            self::Renewed => 'Renewed',
            self::Lost => 'Lost',
        };
    }

    /** Forecast probability 0–100 for revenue weighting. */
    public function winProbability(): int
    {
        return match ($this) {
            self::LeadGenerated, self::Contacted, self::Interested => 10,
            self::MeetingScheduled => 20,
            self::DemoCompleted => 30,
            self::ProposalSent => 50,
            self::Negotiation => 75,
            self::Won, self::Onboarding, self::HiringActive, self::Renewed => 100,
            self::Lost => 0,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Won, self::Onboarding, self::HiringActive, self::Renewed, self::Lost], true);
    }
}
