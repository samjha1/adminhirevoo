@extends('layouts.app')

@section('title', 'Ads Manager leads')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Ads Manager lead files</h1>
            <div class="page-subtitle">Upload CSV/Excel files to AWS — advertisers download or email the same file</div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 shadow-soft mb-4" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger shadow-soft mb-4" role="alert">
            <strong>Upload failed:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('admin.ads-manager.leads.upload-bulk') }}" enctype="multipart/form-data" class="card shadow-soft h-100">
                @csrf
                <div class="card-header fw-semibold">Upload file</div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Upload <strong>.csv</strong> or <strong>.xlsx</strong>. Stored on AWS — not imported row-by-row.</p>
                    @error('csv_file')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror
                    @if($advertisers->isEmpty())
                        <div class="alert alert-warning small">No advertisers found in Ads Manager. Create an advertiser account first.</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Assign to advertiser <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required @disabled($advertisers->isEmpty())>
                            @foreach($advertisers as $advertiser)
                                <option value="{{ $advertiser->id }}" @selected(old('user_id') == $advertiser->id)>{{ $advertiser->name }} ({{ $advertiser->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Campaign (optional)</label>
                        <select name="campaign_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->name }} ({{ $campaign->advertiser?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,.txt,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required @disabled($advertisers->isEmpty())>
                        <p class="form-text">Max 50 MB. Supported: .csv, .xlsx</p>
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled($advertisers->isEmpty())><i class="bi bi-upload me-1"></i>Upload</button>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form method="POST" action="{{ route('admin.ads-manager.leads.assign') }}" id="assign-form" class="card shadow-soft h-100">
                @csrf
                <div class="card-header fw-semibold">Assign files to campaign</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign</label>
                        <select name="campaign_id" class="form-select" required>
                            <option value="">Select campaign…</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->name }} ({{ $campaign->advertiser?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="small text-muted mb-3">Select files in the table below, then assign.</p>
                    <button type="submit" class="btn btn-outline-primary">Assign selected</button>
                </div>
            </form>
        </div>
    </div>

    <form class="card shadow-soft mb-3" method="GET" action="{{ route('admin.ads-manager.leads.index') }}">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1" style="min-width:220px">
                <label class="form-label small text-muted">Search</label>
                <input class="form-control" name="q" placeholder="File name or advertiser" value="{{ request('q') }}">
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="unassigned" value="1" id="unassigned" @checked(request('unassigned')) onchange="this.form.submit()">
                <label class="form-check-label" for="unassigned">Unassigned only</label>
            </div>
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>

    <div class="card shadow-soft">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" id="select-all"></th>
                    <th>File</th>
                    <th>Advertiser</th>
                    <th>Campaign</th>
                    <th>Size</th>
                    <th>Uploaded</th>
                </tr>
                </thead>
                <tbody>
                @forelse($leadFiles as $file)
                    <tr>
                        <td><input type="checkbox" name="file_ids[]" value="{{ $file->id }}" form="assign-form" class="file-checkbox"></td>
                        <td class="fw-semibold">{{ $file->original_filename }} <span class="badge text-bg-light text-uppercase">{{ $file->format }}</span></td>
                        <td class="small text-muted">{{ $file->advertiser?->name ?? '—' }}</td>
                        <td>{{ $file->campaign?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ number_format($file->file_size / 1024, 1) }} KB</td>
                        <td class="small text-muted">{{ $file->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">No files yet. Upload a CSV or Excel file above.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $leadFiles->links() }}</div>
@endsection

@push('scripts')
<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
