<?php

namespace App\Enums;

enum LeadSalesStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Converted = 'converted';
}
