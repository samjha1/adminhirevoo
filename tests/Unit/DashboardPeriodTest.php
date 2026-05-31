<?php

namespace Tests\Unit;

use App\Support\DashboardPeriod;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    public function test_previous_period_has_equal_length(): void
    {
        $period = DashboardPeriod::forPreset('this_month');
        $prev = $period->previous();

        $currentDays = $period->start->diffInDays($period->end) + 1;
        $prevDays = $prev->start->diffInDays($prev->end) + 1;

        $this->assertEqualsWithDelta($currentDays, $prevDays, 1);
        $this->assertTrue($prev->end->lt($period->start));
    }
}
