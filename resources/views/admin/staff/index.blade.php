@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    @php $mgrOnly = $managerCreatesEmployeesOnly ?? false; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">@if($mgrOnly) Sales team @else Staff &amp; roles @endif</h1>
            <div class="page-subtitle">
                @if($mgrOnly)
                    Sales employees reporting to you
                @else
                    Admin panel users (not Hirevo candidates)
                @endif
            </div>
        </div>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-primary">@if($mgrOnly) Add sales employee @else Add staff @endif</a>
    </div>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.staff.index') }}">
        <div class="col-auto">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email">
        </div>
        @if(!$mgrOnly)
        <div class="col-auto">
            <select name="role" class="form-select">
                <option value="">All roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="sales_team" class="form-select">
                <option value="">All teams</option>
                @foreach($salesTeams as $team)
                    <option value="{{ $team->value }}" @selected(request('sales_team') === $team->value)>{{ $team->shortLabel() }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    @if(!$mgrOnly)
                    <th>Role</th>
                    <th>Team</th>
                    @endif
                    <th>Manager</th>
                    @if(!$mgrOnly)
                    <th>Referral code</th>
                    @endif
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($staff as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        @if(!$mgrOnly)
                        <td><span class="badge text-bg-primary">{{ $user->role->label() }}</span></td>
                        <td>
                            @if($user->sales_team)
                                <span class="badge text-bg-light border">{{ $user->sales_team->shortLabel() }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td>{{ $user->manager?->name ?? '—' }}</td>
                        @if(!$mgrOnly)
                        <td>
                            @if($user->referral_code)
                                <code>{{ $user->referral_code }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td class="text-muted small">{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.staff.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @if($user->id !== auth('admin')->id())
                                <form method="POST" action="{{ route('admin.staff.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Delete this staff user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $staff->links() }}</div>
@endsection
