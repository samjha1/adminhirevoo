<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class DashboardPeriod
{
    public const PRESETS = [
        'today',
        'yesterday',
        'this_week',
        'last_week',
        'this_month',
        'last_month',
        'this_quarter',
        'this_year',
        'custom',
    ];

    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $key,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $key = $request->query('period', 'this_month');
        if (! in_array($key, self::PRESETS, true)) {
            $key = 'this_month';
        }

        if ($key === 'custom') {
            $start = $request->query('from')
                ? Carbon::parse($request->query('from'))->startOfDay()
                : now()->startOfMonth();
            $end = $request->query('to')
                ? Carbon::parse($request->query('to'))->endOfDay()
                : now()->endOfDay();

            return new self($start, $end, $key);
        }

        return self::forPreset($key);
    }

    public static function forPreset(string $key): self
    {
        $now = now();

        [$start, $end] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        return new self($start, $end, $key);
    }

    public function previous(): self
    {
        $start = $this->start->copy()->startOfDay();
        $end = $this->end->copy()->startOfDay();
        $days = max(1, (int) $start->diffInDays($end) + 1);
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        return new self($prevStart, $prevEnd, $this->key.'_prev');
    }

    public function label(): string
    {
        return match ($this->key) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This week',
            'last_week' => 'Last week',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
            'custom' => $this->start->format('M j').' – '.$this->end->format('M j, Y'),
            default => 'This month',
        };
    }

    /** @return array<string, string> */
    public function queryParams(): array
    {
        $params = ['period' => $this->key];
        if ($this->key === 'custom') {
            $params['from'] = $this->start->toDateString();
            $params['to'] = $this->end->toDateString();
        }

        return $params;
    }
}
