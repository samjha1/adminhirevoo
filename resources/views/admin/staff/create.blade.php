@extends('layouts.app')

@section('title', 'Add staff')

@section('content')
    @php $mgrOnly = $managerCreatesEmployeesOnly ?? false; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">@if($mgrOnly) Add sales employee @else Add staff user @endif</h1>
            <div class="page-subtitle">
                @if($mgrOnly)
                    New login for your {{ ($lockedTeam ?? null)?->shortLabel() ?? 'sales' }} team.
                @else
                    Assign role and sales team so the user sees the correct pipeline.
                @endif
            </div>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="staff-form-hint mb-3">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Sales team matters:</strong> Talent staff work on candidate leads; Company staff work on employer B2B deals.
        Marketing and admins can access both pipelines.
    </div>

    <div class="card shadow-soft staff-form-card">
        <div class="card-body">
            @include('admin.partials._validation-alert')
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                @include('admin.staff._form')
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>
@endsection
