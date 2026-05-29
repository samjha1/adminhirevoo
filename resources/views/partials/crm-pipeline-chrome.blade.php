@php
    use App\Enums\SalesTeam;
    /** @var SalesTeam $pipeline */
    $isTalent = $pipeline === SalesTeam::Candidate;
    $admin = auth('admin')->user();
    $showBoth = $admin->role?->hasUnrestrictedLeadVisibility() || $admin->role === \App\Enums\AdminRole::Marketing;
@endphp
<div class="crm-pipeline-chrome mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="crm-pipeline-icon {{ $isTalent ? 'talent' : 'company' }}">
                <i class="bi {{ $pipeline->icon() }}"></i>
            </div>
            <div>
                <div class="crm-pipeline-kicker">{{ $isTalent ? 'Talent sales' : 'Company sales' }}</div>
                <h1 class="crm-pipeline-title mb-0">{{ $pipeline->pipelineTitle() }}</h1>
                <p class="crm-pipeline-sub mb-0">
                    @if($isTalent)
                        Candidates applying to jobs — your team calls and converts job seekers.
                    @else
                        Employers posting on Hirevo — your team onboards companies and closes B2B sales.
                    @endif
                </p>
            </div>
        </div>
        @if($showBoth)
            <div class="btn-group crm-pipeline-switch" role="group">
                <a href="{{ route('admin.leads.index') }}"
                   class="btn btn-sm {{ $isTalent ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-person-workspace me-1"></i>Talent pipeline
                </a>
                <a href="{{ route('admin.employers.pipeline.index') }}"
                   class="btn btn-sm {{ ! $isTalent ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-buildings me-1"></i>Company pipeline
                </a>
            </div>
        @endif
    </div>
</div>
