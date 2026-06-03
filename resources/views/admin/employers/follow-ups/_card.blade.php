@php
    /** @var \App\Support\CompanyScheduleItem $item */
    $prospect = $item->followUp?->employerProspect ?? $item->meeting?->prospect;
    $companyName = $prospect?->company_name ?? 'Company #'.$item->employerProspectId();
    $scheduled = $item->at;
    $isMeeting = $item->kind === 'meeting';
@endphp
<article class="co-fu-card @if($item->isOverdue()) is-overdue @endif @if($item->isDone()) is-done @endif"
         style="@if(!empty($animationDelay)) animation-delay: {{ $animationDelay }} @endif">
    <div class="co-fu-time @if($item->isOverdue()) is-overdue @endif">
        <div class="co-fu-time-hour">{{ $scheduled->format('g:i A') }}</div>
        <div class="co-fu-time-date">{{ $scheduled->format('M j') }}</div>
    </div>
    <div class="co-fu-avatar @if($isMeeting) is-meeting @endif" aria-hidden="true">
        <i class="bi {{ $isMeeting ? 'bi-camera-video' : 'bi-building' }}"></i>
    </div>
    <div class="co-fu-body">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="co-fu-type-badge {{ $item->kind }}">{{ $item->typeLabel() }}</span>
        </div>
        <a href="{{ route('admin.employers.pipeline.show', $item->employerProspectId()) }}" class="co-fu-name">
            {{ $companyName }}
        </a>
        @if($prospect?->contact_name || $prospect?->email)
            <div class="co-fu-meta">
                @if($prospect->contact_name)
                    {{ $prospect->contact_name }}
                    @if($prospect->contact_designation)
                        · {{ $prospect->contact_designation }}
                    @endif
                @endif
                @if($prospect->email)
                    <span class="d-block text-truncate" style="max-width: 280px">{{ $prospect->email }}</span>
                @endif
            </div>
        @endif
        @if($item->notes())
            <p class="co-fu-notes mb-0">{{ $item->notes() }}</p>
        @endif
    </div>
    <div class="co-fu-actions">
        <span class="co-fu-badge {{ $item->statusClass() }}">{{ $item->statusLabel() }}</span>
        @if(! $item->isDone())
            <span class="co-fu-relative @if($item->isOverdue()) urgent @endif">{{ $scheduled->diffForHumans(short: true) }}</span>
        @endif
        @if($item->canComplete())
            @if($isMeeting && $item->meeting)
                <form method="POST" action="{{ route('admin.companies.meetings.complete', $item->meeting) }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success btn-co-fu-done">
                        <i class="bi bi-check2 me-1"></i>Done
                    </button>
                </form>
            @elseif($item->followUp)
                <form method="POST" action="{{ route('admin.follow-ups.complete', $item->followUp) }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-success btn-co-fu-done">
                        <i class="bi bi-check2 me-1"></i>Done
                    </button>
                </form>
            @endif
        @endif
    </div>
</article>
