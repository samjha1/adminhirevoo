<?php

namespace App\Enums;

enum CompanyOutreachStage: string
{
    case New = 'new';
    case Called = 'called';
    case FollowUp = 'follow_up';
    case Interested = 'interested';
    case SignupLinkSent = 'signup_link_sent';
    case SignedUp = 'signed_up';
    case NotInterested = 'not_interested';

    /** @return list<self> */
    public static function ordered(): array
    {
        return [
            self::New,
            self::Called,
            self::FollowUp,
            self::Interested,
            self::SignupLinkSent,
            self::SignedUp,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Called => 'Called',
            self::FollowUp => 'Follow up',
            self::Interested => 'Interested',
            self::SignupLinkSent => 'Signup link sent',
            self::SignedUp => 'Signed up',
            self::NotInterested => 'Not interested',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::SignedUp, self::NotInterested], true);
    }
}
