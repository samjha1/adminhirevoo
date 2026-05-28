@extends('layouts.app')

@section('title', 'Sponsored ads')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Sponsored ads</h1>
            <div class="page-subtitle">Approve Ads Manager creatives for Hirevo (homepage, jobs, dashboard, and more)</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['pending_review' => 'Pending', 'active' => 'Live on Hirevo', 'draft' => 'Draft', 'paused' => 'Paused', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.sponsored-ads.index', array_merge(request()->except('page'), ['status' => $key])) }}"
               class="btn btn-sm {{ ($status ?? 'pending_review') === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
                @if($key === 'pending_review' && ($pendingCount ?? 0) > 0)
                    <span class="badge text-bg-light ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form class="card shadow-soft mb-3" method="GET" action="{{ route('admin.sponsored-ads.index') }}">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label class="form-label small text-muted">Search</label>
                <input class="form-control" name="q" placeholder="Ad name or headline" value="{{ request('q') }}" style="min-width:220px">
            </div>
            <div>
                <label class="form-label small text-muted">Hirevo screen</label>
                <select name="placement" class="form-select" style="min-width:220px">
                    <option value="">All screens</option>
                    @foreach($placements as $key => $label)
                        <option value="{{ $key }}" @selected(request('placement') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Ad</th>
                    <th>Advertiser</th>
                    <th>Hirevo screen</th>
                    <th>Campaign</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($ads as $ad)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if($ad->image_url)
                                    <img src="{{ $ad->image_url }}" alt="" class="rounded" width="56" height="36" style="object-fit:cover">
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $ad->name }}</div>
                                    <div class="small text-muted">{{ Str::limit($ad->headline, 48) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small">{{ $ad->advertiser?->email ?? '—' }}</td>
                        <td><span class="badge text-bg-light">{{ $ad->placementLabel() }}</span></td>
                        <td class="small">
                            {{ $ad->campaign?->name ?? '—' }}
                            @if($ad->campaign && $ad->campaign->status !== 'active')
                                <span class="badge text-bg-warning">campaign inactive</span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-{{ $ad->statusBadge() }} text-capitalize">{{ str_replace('_', ' ', $ad->status) }}</span></td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                @if($ad->status === 'pending_review')
                                    <form method="POST" action="{{ route('admin.sponsored-ads.approve', $ad) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sponsored-ads.reject', $ad) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @elseif($ad->status === 'active')
                                    <form method="POST" action="{{ route('admin.sponsored-ads.pause', $ad) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Pause</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.sponsored-ads.show', $ad) }}" class="btn btn-sm btn-outline-primary">Preview</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">No ads in this queue.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $ads->links() }}</div>
@endsection
