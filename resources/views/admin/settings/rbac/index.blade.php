@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Roles &amp; permissions</h1>
            <p class="page-subtitle mb-0">Matrix of CRM roles and granular permission slugs.</p>
        </div>
    </div>

    @foreach($roles as $role)
        <div class="card shadow-soft mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $role->name }}</strong>
                    <span class="text-muted small ms-2"><code>{{ $role->slug }}</code></span>
                    @if($role->is_system)
                        <span class="badge text-bg-secondary ms-2">System</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($role->slug === 'super_admin')
                    <p class="text-muted mb-0">Super Admin always has all permissions (not editable).</p>
                @else
                    <form method="POST" action="{{ route('admin.settings.rbac.update', $role) }}">
                        @csrf
                        @method('PUT')
                        @foreach($permissionsByGroup as $group => $permissions)
                            <h6 class="text-uppercase text-muted mt-3">{{ $group }}</h6>
                            <div class="row">
                                @foreach($permissions as $permission)
                                    <div class="col-md-4 mb-2">
                                        <label class="d-flex align-items-start gap-2">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                @checked($role->permissions->contains('id', $permission->id))>
                                            <span>
                                                <span class="fw-semibold">{{ $permission->name }}</span>
                                                <br><code class="small">{{ $permission->slug }}</code>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-primary mt-3">Save {{ $role->name }}</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
@endsection
