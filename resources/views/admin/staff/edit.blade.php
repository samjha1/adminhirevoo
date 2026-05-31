@extends('layouts.app')

@section('title', 'Edit staff')

@section('content')
    @php $mgrOnly = $managerCreatesEmployeesOnly ?? false; @endphp
    <div class="page-header">
        <div>
            <h1 class="page-title">Edit {{ $staff->name }}</h1>
            @if($staff->sales_team)
                <div class="page-subtitle">{{ $staff->sales_team->label() }}</div>
            @endif
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-soft staff-form-card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
                @csrf
                @method('PUT')
                @include('admin.staff._form', ['staff' => $staff])
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
@endsection
