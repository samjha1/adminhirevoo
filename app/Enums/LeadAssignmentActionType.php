<?php

namespace App\Enums;

enum LeadAssignmentActionType: string
{
    case Assign = 'assign';
    case Unassign = 'unassign';
    case Reassign = 'reassign';
    case TakeBack = 'take_back';
}
