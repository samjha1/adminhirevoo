<?php

namespace App\Enums;

enum LeadAssignmentStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Closed = 'closed';
}
