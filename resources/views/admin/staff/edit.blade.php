@extends('layouts.app')

@section('title', 'Edit staff')

@section('content')
    @php $mgrOnly = $managerCreatesEmployeesOnly ?? false; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit {{ $staff->name }}</h1>
            @if($mgrOnly)
                <div class="page-subtitle">Sales employee on your team</div>
            @endif
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-soft" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $staff->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New password (optional)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
                @if($mgrOnly)
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="{{ $staff->role->label() }}" disabled>
                    </div>
                @else
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->value }}" @selected(old('role', $staff->role->value) === $r->value)>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reports to (sales employees only)</label>
                        <select name="manager_id" class="form-select">
                            <option value="">—</option>
                            @foreach($managers as $m)
                                @if($m->id === $staff->id)
                                    @continue
                                @endif
                                <option value="{{ $m->id }}" @selected((string) old('manager_id', $staff->manager_id) === (string) $m->id)>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
@endsection
