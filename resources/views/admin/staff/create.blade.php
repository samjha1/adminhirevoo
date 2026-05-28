@extends('layouts.app')

@section('title', 'Add staff')

@section('content')
    @php $mgrOnly = $managerCreatesEmployeesOnly ?? false; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">@if($mgrOnly) Add sales employee @else Add staff user @endif</h1>
            <div class="page-subtitle">
                @if($mgrOnly)
                    Creates a login for a sales employee on your team (you are set as their manager).
                @else
                    Creates a login for the admin panel
                @endif
            </div>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-soft" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                @if($mgrOnly)
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="Sales employee" disabled>
                        <div class="form-text">Sales managers can only create accounts for sales employees reporting to them.</div>
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->value }}" @selected(old('role') === $r->value)>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reports to (sales employees only)</label>
                        <select name="manager_id" class="form-select">
                            <option value="">—</option>
                            @foreach($managers as $m)
                                <option value="{{ $m->id }}" @selected((string) old('manager_id') === (string) $m->id)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>
@endsection
