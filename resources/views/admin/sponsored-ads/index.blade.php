@extends('layouts.app')

@section('title', 'Sponsored ads')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Sponsored ads</h1>
            <div class="page-subtitle">Review and approve Ads Manager creatives for Hirevo</div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['under_review' => 'Pending review', 'active' => 'Live on Hirevo', 'approved' => 'Approved', 'rejected' => 'Rejected', 'draft' => 'Draft', 'paused' => 'Paused', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.sponsored-ads.index', array_merge(request()->except('page'), ['status' => $key])) }}"
               class="btn btn-sm {{ ($status ?? 'under_review') === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
                @if($key === 'under_review' && ($pendingCount ?? 0) > 0)
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
                <label class="form-label small text-muted">Placement</label>
                <select name="placement" class="form-select" style="min-width:220px">
                    <option value="">All placements</option>
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
                    <th>Placement</th>
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
                                @if($ad->displayImageUrl())
                                    <img src="{{ $ad->displayImageUrl() }}" alt="" class="rounded" width="56" height="36" style="object-fit:cover">
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $ad->name }}</div>
                                    <div class="small text-muted">{{ Str::limit($ad->headline, 48) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small">{{ $ad->advertiser?->name ?? $ad->advertiser?->email ?? '—' }}</td>
                        <td><span class="badge text-bg-light">{{ $ad->placementLabel() }}</span></td>
                        <td class="small">
                            {{ $ad->campaign?->name ?? '—' }}
                            @if($ad->campaign && ! in_array($ad->campaign->status, ['active', 'approved'], true))
                                <span class="badge text-bg-warning">campaign not live</span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-{{ $ad->statusBadge() }}">{{ $ad->statusLabel() }}</span></td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                @if($ad->isPendingReview())
                                    <form method="POST" action="{{ route('admin.sponsored-ads.approve', $ad) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-modal-{{ $ad->id }}">Reject</button>
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

    @foreach($ads as $ad)
        @if($ad->isPendingReview())
            <div class="modal fade" id="reject-modal-{{ $ad->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('admin.sponsored-ads.reject', $ad) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Reject ad</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Why is this creative being rejected?"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
