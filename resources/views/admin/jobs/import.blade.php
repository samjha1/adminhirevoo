@extends('layouts.app')

@section('title', 'Upload jobs')

@section('content')
    @include('partials.portal-ui')

    <div class="portal-page">
        @include('partials.portal-nav', ['active' => 'jobs'])

        <div class="portal-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="portal-hero-kicker">Job Portal</div>
                <h1 class="portal-hero-title">Bulk upload jobs</h1>
                <p class="portal-hero-sub">Import catalog jobs from CSV into Hirevo. Each row’s <strong>company_name</strong> shows on the public job board (not “Hirevo Catalog”).</p>
            </div>
            <div class="portal-hero-actions d-flex flex-wrap gap-2">
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                    <i class="bi bi-arrow-left me-1"></i>Back to jobs
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success shadow-soft">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning shadow-soft">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger shadow-soft">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger shadow-soft">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="portal-filters-card h-100">
                    <div class="portal-filters-head">
                        <h2><i class="bi bi-upload text-primary"></i> Upload CSV</h2>
                    </div>
                    <div class="portal-filters-body">
                        <form method="POST" action="{{ route('admin.jobs.import.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">CSV file <span class="text-danger">*</span></label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                                <div class="form-text">Max {{ number_format($maxKb / 1024, 1) }} MB · up to {{ number_format($maxRows) }} rows per upload.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catalog employer account</label>
                                <input type="email" name="catalog_email" class="form-control" value="{{ old('catalog_email', $catalogEmail) }}" placeholder="{{ $catalogEmail }}">
                                <div class="form-text">Jobs attach to this Hirevo referrer account (default catalog employer). Company names still come from each CSV row.</div>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="skip_duplicates" name="skip_duplicates" value="1" @checked(old('skip_duplicates', '1'))>
                                <label class="form-check-label" for="skip_duplicates">Skip duplicates (same title + company)</label>
                            </div>
                            <button type="submit" class="btn btn-primary px-4" style="border-radius:10px;">
                                <i class="bi bi-cloud-upload me-1"></i>Import jobs
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="portal-filters-card h-100">
                    <div class="portal-filters-head">
                        <h2><i class="bi bi-file-earmark-spreadsheet text-primary"></i> Templates</h2>
                    </div>
                    <div class="portal-filters-body d-flex flex-column gap-2">
                        <a href="{{ route('admin.jobs.import.template') }}" class="btn btn-outline-primary" style="border-radius:10px;">
                            <i class="bi bi-download me-1"></i>Download empty CSV template
                        </a>
                        @if($hasSampleCsv)
                            <a href="{{ route('admin.jobs.import.sample') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                                <i class="bi bi-download me-1"></i>Download sample (500 jobs)
                            </a>
                        @endif
                        <hr class="my-2">
                        <p class="small text-muted mb-2 fw-semibold">Required columns</p>
                        <p class="small text-muted mb-0" style="line-height:1.6;">{{ implode(', ', $templateHeaders) }}</p>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><code>required_skills</code> — pipe-separated, e.g. Java|SQL</li>
                            <li><code>apply_link</code> — external careers URL</li>
                            <li><code>display_applications_count</code> — optional “X applied” on cards</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
