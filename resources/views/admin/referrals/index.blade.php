@extends('layouts.app')

@section('title', 'Referrals')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Referrals</h1>
            <div class="page-subtitle">Review and update referral request outcomes</div>
        </div>
        <form class="d-flex gap-2" method="GET" action="{{ route('admin.referrals.index') }}">
            <select class="form-select" name="status" style="width: 180px;">
                <option value="">All status</option>
                @foreach(['pending','accepted','rejected','hired','reward_paid'] as $status)
                    <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Candidate</th><th>Referrer</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                <tbody>
                @forelse($referrals as $referral)
                    <tr>
                        <td>#{{ $referral->id }}</td>
                        <td>{{ $referral->candidate?->name ?? '—' }}</td>
                        <td>{{ $referral->referrer?->name ?? '—' }}</td>
                        <td><span class="badge text-bg-light text-capitalize">{{ str_replace('_', ' ', $referral->status) }}</span></td>
                        <td class="text-muted">{{ $referral->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.referrals.status', $referral->id) }}" class="d-inline-flex gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="width: 160px;">
                                    @foreach(['pending','accepted','rejected','hired','reward_paid'] as $status)
                                        <option value="{{ $status }}" @selected($referral->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No referrals found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $referrals->links() }}</div>
@endsection

