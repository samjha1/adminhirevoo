@extends('layouts.app')

@section('title', 'Consultation Details')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Consultation #{{ $consultation->id }}</h1>
            <div class="page-subtitle">Full details from `career_consultation_requests` table</div>
        </div>
        <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-soft">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>User:</strong><br>{{ $consultation->user?->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Status:</strong><br>{{ $consultation->status }}</div>
                <div class="col-md-4"><strong>Source:</strong><br>{{ $consultation->source }}</div>
                <div class="col-md-4"><strong>Match %:</strong><br>{{ (int) ($consultation->match_percentage ?? 0) }}</div>
                <div class="col-md-4"><strong>Job Role ID:</strong><br>{{ $consultation->job_role_id ?? '—' }}</div>
                <div class="col-md-4"><strong>Resume ID:</strong><br>{{ $consultation->resume_id ?? '—' }}</div>
                <div class="col-md-6"><strong>Created:</strong><br>{{ $consultation->created_at?->format('Y-m-d H:i:s') }}</div>
                <div class="col-md-6"><strong>Updated:</strong><br>{{ $consultation->updated_at?->format('Y-m-d H:i:s') }}</div>
                <div class="col-12"><strong>Gap Skills (JSON):</strong><pre class="bg-light p-3 rounded border mt-1">{{ json_encode($consultation->gap_skills, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></div>
                <div class="col-12"><strong>Suggested Gap Skills (JSON):</strong><pre class="bg-light p-3 rounded border mt-1">{{ json_encode($consultation->suggested_gap_skills, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></div>
                <div class="col-12"><strong>Matched Skills (JSON):</strong><pre class="bg-light p-3 rounded border mt-1">{{ json_encode($consultation->matched_skills, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div>
        </div>
    </div>

    <div class="card shadow-soft mt-4">
        <div class="card-header bg-white fw-semibold">Related Leads</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Status</th><th>Match %</th><th>Intent</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @forelse($relatedLeads as $lead)
                    <tr>
                        <td>#{{ $lead->id }}</td>
                        <td>{{ $lead->status }}</td>
                        <td>{{ (int) ($lead->match_percentage ?? 0) }}</td>
                        <td>{{ (int) ($lead->intent_score ?? 0) }}</td>
                        <td>{{ $lead->updated_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end"><a href="{{ route('admin.leads.show', $lead->id) }}" class="btn btn-sm btn-outline-secondary">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No related leads.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

