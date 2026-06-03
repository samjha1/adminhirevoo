@extends('layouts.app')

@section('title', 'Company schedule')

@push('styles')
<style>
    .co-fu-page { max-width: 960px; margin: 0 auto; width: 100%; }
    .co-fu-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    @media (max-width: 768px) {
        .co-fu-stats { grid-template-columns: repeat(2, 1fr); }
        .co-fu-card { flex-wrap: wrap; }
        .co-fu-actions {
            flex-direction: row;
            align-items: center;
            width: 100%;
            justify-content: flex-start;
            padding-top: .5rem;
            border-top: 1px solid #f1f5f9;
        }
    }
    .co-fu-stat {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 4px 18px rgba(15, 23, 42, .04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .co-fu-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(5, 150, 105, .1);
    }
    .co-fu-stat-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
        margin-bottom: .35rem;
    }
    .co-fu-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: #0f172a;
        line-height: 1;
    }
    .co-fu-stat.stat-overdue .co-fu-stat-value { color: #b45309; }
    .co-fu-stat.stat-today .co-fu-stat-value { color: #047857; }
    .co-fu-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .co-fu-segment {
        display: inline-flex;
        padding: 4px;
        background: #f1f5f9;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
    }
    .co-fu-segment a {
        padding: .45rem 1rem;
        font-size: .8rem;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-radius: 999px;
        transition: background .2s ease, color .2s ease, box-shadow .2s ease;
    }
    .co-fu-segment a:hover { color: #047857; }
    .co-fu-segment a.active {
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff;
        box-shadow: 0 4px 14px rgba(5, 150, 105, .35);
    }
    .co-fu-section {
        margin-bottom: 1.5rem;
    }
    .co-fu-section-hd {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .75rem;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #64748b;
    }
    .co-fu-section-hd.overdue { color: #b45309; }
    .co-fu-section-hd .count {
        background: rgba(15, 23, 42, .06);
        padding: .1rem .45rem;
        border-radius: 999px;
        font-size: .7rem;
    }
    .co-fu-section-hd.overdue .count {
        background: #fffbeb;
        color: #b45309;
    }
    .co-fu-list { display: flex; flex-direction: column; gap: .65rem; }
    .co-fu-card {
        display: flex;
        align-items: stretch;
        gap: 1rem;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        padding: 1rem 1.15rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, .05);
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        animation: coFuFadeIn .35s ease backwards;
    }
    .co-fu-card:hover {
        border-color: rgba(5, 150, 105, .25);
        box-shadow: 0 10px 32px rgba(5, 150, 105, .12);
        transform: translateY(-1px);
    }
    .co-fu-card.is-overdue {
        border-color: #fde68a;
        background: linear-gradient(135deg, #fffbeb 0%, #fff 40%);
    }
    .co-fu-card.is-done { opacity: .72; }
    @keyframes coFuFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .co-fu-time {
        flex-shrink: 0;
        width: 80px;
        text-align: center;
        padding: .5rem .35rem;
        border-radius: 12px;
        background: linear-gradient(180deg, #f0fdf4, #ecfdf5);
        border: 1px solid #bbf7d0;
    }
    .co-fu-time.is-overdue {
        background: linear-gradient(180deg, #fffbeb, #fef3c7);
        border-color: #fde68a;
    }
    .co-fu-time-hour {
        font-size: .82rem;
        font-weight: 800;
        color: #047857;
        line-height: 1.1;
        letter-spacing: -.02em;
    }
    .co-fu-time.is-overdue .co-fu-time-hour { color: #b45309; }
    .co-fu-time-date {
        font-size: .65rem;
        font-weight: 600;
        color: #64748b;
        margin-top: .2rem;
    }
    .co-fu-avatar {
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        background: linear-gradient(135deg, #059669, #34d399);
    }
    .co-fu-body { flex: 1; min-width: 0; }
    .co-fu-name {
        font-weight: 700;
        font-size: .95rem;
        color: #0f172a;
        text-decoration: none;
        transition: color .15s ease;
    }
    .co-fu-name:hover { color: #047857; }
    .co-fu-meta {
        font-size: .78rem;
        color: #64748b;
        margin-top: .15rem;
    }
    .co-fu-notes {
        font-size: .8rem;
        color: #475569;
        margin-top: .5rem;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .co-fu-actions {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: center;
        gap: .5rem;
    }
    .co-fu-badge {
        font-size: .68rem;
        font-weight: 700;
        padding: .28rem .55rem;
        border-radius: 999px;
        border: 1px solid transparent;
        white-space: nowrap;
    }
    .co-fu-badge.pending { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .co-fu-badge.overdue { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .co-fu-badge.completed { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .co-fu-badge.meeting { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
    .co-fu-avatar.is-meeting { background: linear-gradient(135deg, #ea580c, #fb923c); }
    .co-fu-type-badge {
        font-size: .62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: .15rem .45rem;
        border-radius: 6px;
    }
    .co-fu-type-badge.follow_up { background: #eff6ff; color: #1d4ed8; }
    .co-fu-type-badge.meeting { background: #fff7ed; color: #c2410c; }
    .co-fu-relative {
        font-size: .72rem;
        font-weight: 600;
        color: #94a3b8;
    }
    .co-fu-relative.urgent { color: #b45309; }
    .btn-co-fu-done {
        font-size: .75rem;
        font-weight: 600;
        padding: .35rem .85rem;
        border-radius: 999px;
        white-space: nowrap;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-co-fu-done:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(5, 150, 105, .3);
    }
    .co-fu-empty {
        text-align: center;
        padding: 3.5rem 1.5rem;
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 20px;
    }
    .co-fu-empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1.25rem;
        border-radius: 18px;
        background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        color: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }
    .co-fu-pagination {
        margin-top: 1.25rem;
        padding: 1rem 1.25rem;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #f1f5f9;
    }
</style>
@endpush

@section('content')
    @php
        $stats = $stats ?? ['today' => 0, 'overdue' => 0, 'upcoming' => 0, 'total_open' => 0];
        $overdueItems = $overdueItems ?? collect();
        $scheduleItems = $scheduleItems ?? collect();
        $hasOverdue = $overdueItems->isNotEmpty();
        $hasScheduled = $scheduleItems instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $scheduleItems->count() > 0
            : $scheduleItems->isNotEmpty();
        $isEmpty = ! $hasOverdue && ! $hasScheduled;
        $listCount = $scheduleItems instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $scheduleItems->total()
            : $scheduleItems->count();
    @endphp

    <div class="co-fu-page">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])

        <div class="co-fu-stats" role="group" aria-label="Schedule summary">
            <div class="co-fu-stat stat-today">
                <div class="co-fu-stat-label">Due today</div>
                <div class="co-fu-stat-value">{{ number_format($stats['today']) }}</div>
            </div>
            <div class="co-fu-stat stat-overdue">
                <div class="co-fu-stat-label">Overdue</div>
                <div class="co-fu-stat-value">{{ number_format($stats['overdue']) }}</div>
            </div>
            <div class="co-fu-stat">
                <div class="co-fu-stat-label">Upcoming</div>
                <div class="co-fu-stat-value">{{ number_format($stats['upcoming']) }}</div>
            </div>
            <div class="co-fu-stat">
                <div class="co-fu-stat-label">Open total</div>
                <div class="co-fu-stat-value">{{ number_format($stats['total_open']) }}</div>
            </div>
        </div>

        <div class="co-fu-toolbar">
            <div class="co-fu-segment" role="tablist">
                <a href="{{ route('admin.companies.follow-ups.today') }}"
                   class="@if($filter === 'today') active @endif"
                   role="tab"
                   @if($filter === 'today') aria-selected="true" @endif>
                    <i class="bi bi-sun me-1"></i>Today
                </a>
                <a href="{{ route('admin.companies.follow-ups.index') }}"
                   class="@if($filter === 'all') active @endif"
                   role="tab"
                   @if($filter === 'all') aria-selected="true" @endif>
                    <i class="bi bi-calendar3 me-1"></i>All open
                </a>
            </div>
            <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-buildings me-1"></i>Companies
            </a>
        </div>

        @if($filter === 'today' && $hasOverdue)
            <section class="co-fu-section" aria-labelledby="fu-overdue-hd">
                <div class="co-fu-section-hd overdue" id="fu-overdue-hd">
                    <i class="bi bi-exclamation-circle"></i>
                    Overdue
                    <span class="count">{{ $overdueItems->count() }}</span>
                </div>
                <div class="co-fu-list">
                    @foreach($overdueItems as $index => $item)
                        @include('admin.employers.follow-ups._card', [
                            'item' => $item,
                            'animationDelay' => ($index * 40).'ms',
                        ])
                    @endforeach
                </div>
            </section>
        @endif

        @if($hasScheduled)
            <section class="co-fu-section" aria-labelledby="fu-list-hd">
                @if($filter === 'today')
                    <div class="co-fu-section-hd" id="fu-list-hd">
                        <i class="bi bi-calendar-check"></i>
                        {{ $hasOverdue ? 'Scheduled today' : "Today's schedule" }}
                        <span class="count">{{ $listCount }}</span>
                    </div>
                @else
                    <div class="co-fu-section-hd" id="fu-list-hd">
                        <i class="bi bi-list-check"></i>
                        Open follow-ups &amp; meetings
                        <span class="count">{{ $listCount }}</span>
                    </div>
                @endif
                <div class="co-fu-list">
                    @foreach($scheduleItems as $index => $item)
                        @include('admin.employers.follow-ups._card', [
                            'item' => $item,
                            'animationDelay' => ($index * 40).'ms',
                        ])
                    @endforeach
                </div>
            </section>
        @endif

        @if($isEmpty)
            <div class="co-fu-empty">
                <div class="co-fu-empty-icon"><i class="bi bi-calendar-check"></i></div>
                <h2 class="h5 fw-bold text-dark mb-2">
                    @if($filter === 'today')
                        Nothing scheduled for today
                    @else
                        No open follow-ups or meetings
                    @endif
                </h2>
                <p class="text-muted small mb-3 mx-auto" style="max-width: 400px">
                    @if($filter === 'today')
                        You're caught up for today. Set a company to <strong>Follow up</strong> or <strong>Meeting scheduled</strong> on its profile and enter the date.
                    @else
                        Follow-ups and meetings appear here when you schedule them from the company pipeline.
                    @endif
                </p>
                <a href="{{ route('admin.employers.pipeline.index') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-buildings me-1"></i>Browse companies
                </a>
            </div>
        @endif

        @if($scheduleItems instanceof \Illuminate\Pagination\LengthAwarePaginator && $scheduleItems->hasPages())
            <div class="co-fu-pagination">
                @include('partials.crm-pagination-footer', ['paginator' => $scheduleItems])
            </div>
        @endif
    </div>
@endsection
