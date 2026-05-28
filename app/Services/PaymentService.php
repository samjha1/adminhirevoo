<?php

namespace App\Services;

use App\Models\Hirevo\HirevoPayment;

class PaymentService
{
    public function totalCompletedRevenue(): float
    {
        return (float) HirevoPayment::query()->where('status', 'completed')->sum('amount');
    }
}

