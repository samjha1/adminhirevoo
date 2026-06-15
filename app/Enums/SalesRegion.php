<?php

namespace App\Enums;

enum SalesRegion: string
{
    case North = 'north';
    case South = 'south';

    public function label(): string
    {
        return match ($this) {
            self::North => 'North',
            self::South => 'South',
        };
    }

    /** Normalize enum, string, or null to a region slug. */
    public static function normalize(mixed $region): ?string
    {
        if ($region instanceof self) {
            return $region->value;
        }

        if (is_string($region) && $region !== '') {
            return self::tryFrom($region)?->value;
        }

        return null;
    }
}
