@extends('layouts.app')

@section('title', 'Import company outreach leads')

@section('content')
    <div class="company-page" style="max-width: 720px; margin: 0 auto;">
        @include('partials.crm-pipeline-chrome', ['pipeline' => $pipeline])
        @include('partials.company-section-tabs', ['activeTab' => 'outreach'])

        <div class="page-header mb-3">
            <div>
                <h1 class="page-title h4 mb-1">Import company leads</h1>
                <p class="page-subtitle mb-0 text-muted small">
                    Upload companies that have not signed up on Hirevo yet. Supports CSV and Excel (.xlsx).
                </p>
            </div>
            <a href="{{ route('admin.employers.outreach.template') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download me-1"></i>Download template
            </a>
        </div>

        <div class="card shadow-soft">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.employers.outreach.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Excel or CSV file</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text mt-2">
                            Columns: <code>company_name</code>, <code>contact_name</code>, <code>phone</code>,
                            <code>email</code>, <code>industry</code>, <code>website</code>, <code>location</code>,
                            <code>source</code>, <code>notes</code>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload me-1"></i>Import leads
                        </button>
                        <a href="{{ route('admin.employers.outreach.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
