<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PortalDateFilter
{
    public const PRESETS = [
        'today',
        'last_7_days',
        'last_30_days',
        'this_week',
        'this_month',
        'custom',
    ];

    public function __construct(
        public readonly ?Carbon $start,
        public readonly ?Carbon $end,
        public readonly string $key,
    ) {
    }

    public static function fromRequest(Request $request, string $param = 'period'): self
    {
        $key = (string) $request->query($param, '');

        if ($key === '' || ! in_array($key, self::PRESETS, true)) {
            return new self(null, null, '');
        }

        if ($key === 'custom') {
            $start = $request->query('date_from')
                ? Carbon::parse($request->query('date_from'))->startOfDay()
                : now()->startOfMonth();
            $end = $request->query('date_to')
                ? Carbon::parse($request->query('date_to'))->endOfDay()
                : now()->endOfDay();

            return new self($start, $end, $key);
        }

        $now = now();

        [$start, $end] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [null, null],
        };

        return new self($start, $end, $key);
    }

    public function isActive(): bool
    {
        return $this->key !== '' && $this->start !== null && $this->end !== null;
    }

    public function label(): string
    {
        return match ($this->key) {
            'today' => 'Today',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'custom' => $this->start && $this->end
                ? $this->start->format('M j').' – '.$this->end->format('M j, Y')
                : 'Custom range',
            default => 'All time',
        };
    }

    /** @return array<string, string> */
    public function queryParams(): array
    {
        if (! $this->isActive()) {
            return [];
        }

        $params = ['period' => $this->key];
        if ($this->key === 'custom') {
            $params['date_from'] = $this->start?->toDateString() ?? '';
            $params['date_to'] = $this->end?->toDateString() ?? '';
        }

        return $params;
    }

    public function apply(Builder $query, string $column = 'created_at'): Builder
    {
        if (! $this->isActive()) {
            return $query;
        }

        return $query->whereBetween($column, [$this->start, $this->end]);
    }
}
