@extends('layouts.app')

@section('title', $ad->name)

@section('content')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.sponsored-ads.index') }}" class="small text-muted text-decoration-none">← Sponsored ads</a>
            <h1 class="page-title mt-1">{{ $ad->name }}</h1>
            <div class="page-subtitle">{{ $ad->placementLabel() }} · {{ $ad->advertiser?->email }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($ad->status === 'pending_review')
                <form method="POST" action="{{ route('admin.sponsored-ads.approve', $ad) }}">@csrf
                    <button type="submit" class="btn btn-success">Approve for Hirevo</button>
                </form>
                <form method="POST" action="{{ route('admin.sponsored-ads.reject', $ad) }}">@csrf
                    <button type="submit" class="btn btn-outline-danger">Reject</button>
                </form>
            @elseif($ad->status === 'active')
                <form method="POST" action="{{ route('admin.sponsored-ads.pause', $ad) }}">@csrf
                    <button type="submit" class="btn btn-outline-secondary">Pause on Hirevo</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-soft">
                <div class="card-header fw-semibold">Hirevo preview</div>
                <div class="card-body p-4">
                    <div class="border rounded-3 overflow-hidden" style="max-width:320px">
                        @if($ad->image_url)
                            <img src="{{ $ad->image_url }}" class="w-100" style="aspect-ratio:16/9;object-fit:cover" alt="">
                        @endif
                        <div class="p-3">
                            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size:0.65rem;letter-spacing:.08em">Sponsored</div>
                            <div class="fw-bold">{{ $ad->headline }}</div>
                            @if($ad->body)
                                <p class="small text-muted mb-2 mt-1">{{ $ad->body }}</p>
                            @endif
                            <span class="small fw-semibold text-primary">{{ $ad->cta_label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-soft">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8"><span class="badge text-bg-{{ $ad->statusBadge() }}">{{ str_replace('_', ' ', $ad->status) }}</span></dd>
                        <dt class="col-sm-4 text-muted">Campaign</dt>
                        <dd class="col-sm-8">{{ $ad->campaign?->name ?? '—' }} ({{ $ad->campaign?->status ?? 'n/a' }})</dd>
                        <dt class="col-sm-4 text-muted">Destination</dt>
                        <dd class="col-sm-8 text-break"><a href="{{ $ad->destination_url }}" target="_blank" rel="noopener">{{ $ad->destination_url }}</a></dd>
                        <dt class="col-sm-4 text-muted">Targeting</dt>
                        <dd class="col-sm-8">{{ $ad->target_area ?? '—' }} · {{ $ad->target_age_group ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Stats</dt>
                        <dd class="col-sm-8">{{ number_format($ad->impressions_count) }} views · {{ number_format($ad->clicks_count) }} clicks · {{ number_format($ad->leads_count) }} leads</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
