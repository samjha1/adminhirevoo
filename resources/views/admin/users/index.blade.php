@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Users</h1>
            <div class="page-subtitle">Manage candidate, employer and edtech accounts</div>
        </div>
        <form class="d-flex gap-2" method="GET" action="{{ route('admin.users.index') }}">
            <input class="form-control" name="q" placeholder="Search name/email/phone" value="{{ request('q') }}" style="width: 240px;">
            <select class="form-select" name="role" style="width: 150px;">
                <option value="">All roles</option>
                <option value="candidate" @selected(request('role')==='candidate')>Candidate</option>
                <option value="referrer" @selected(request('role')==='referrer')>Employer</option>
                <option value="edtech" @selected(request('role')==='edtech')>Edtech</option>
            </select>
            <select class="form-select" name="status" style="width: 140px;">
                <option value="">All status</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="blocked" @selected(request('status')==='blocked')>Blocked</option>
                <option value="pending" @selected(request('status')==='pending')>Pending</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </form>
    </div>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td><span class="badge text-bg-light text-capitalize">{{ $user->role === 'referrer' ? 'Employer' : $user->role }}</span></td>
                        <td><span class="badge text-bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'blocked' ? 'danger' : 'warning') }}">{{ ucfirst($user->status) }}</span></td>
                        <td class="text-muted">{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.users.status', $user->id) }}" class="d-inline-flex gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="width: 130px;">
                                    <option value="active" @selected($user->status==='active')>Active</option>
                                    <option value="blocked" @selected($user->status==='blocked')>Blocked</option>
                                    <option value="pending" @selected($user->status==='pending')>Pending</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection

