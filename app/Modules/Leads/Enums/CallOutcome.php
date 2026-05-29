<?php

namespace App\Modules\Leads\Enums;

enum CallOutcome: string
{
    case Connected = 'connected';
    case NoAnswer = 'no_answer';
    case Busy = 'busy';
    case WrongNumber = 'wrong_number';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::NoAnswer => 'No answer',
            self::Busy => 'Busy',
            self::WrongNumber => 'Wrong number',
        };
    }
}
