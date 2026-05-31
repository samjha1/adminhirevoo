<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Hirevo\HirevoLead;
use App\Models\Hirevo\HirevoUser;
use App\Services\LeadVisibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyTalentPipelineCommand extends Command
{
    protected $signature = 'crm:verify-talent-pipeline';

    protected $description = 'Verify Hirevo → CRM talent pipeline (DB, leads, visibility)';

    public function handle(LeadVisibilityService $visibility): int
    {
        $this->info('Talent pipeline verification');
        $this->line('DB: '.config('database.default').' / '.config('database.connections.'.config('database.default').'.database'));

        if (! Schema::hasTable('leads')) {
            $this->error('Table `leads` missing — run Hirevo migrations.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('users')) {
            $this->error('Table `users` missing — shared Hirevo DB required.');

            return self::FAILURE;
        }

        $cols = ['referral_source', 'lead_summary', 'employer_job_id', 'assigned_to', 'assignment_status'];
        foreach ($cols as $col) {
            $ok = Schema::hasColumn('leads', $col);
            $this->line(sprintf('  leads.%s: %s', $col, $ok ? 'OK' : 'MISSING'));
        }

        $total = HirevoLead::query()->count();
        $withCandidate = HirevoLead::query()->whereNotNull('candidate_id')->count();
        $unassigned = HirevoLead::query()->whereNull('assigned_to')->count();

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Total leads', $total],
            ['With candidate', $withCandidate],
            ['Unassigned (CRM pool)', $unassigned],
            ['Demo (crm-demo.*)', HirevoUser::query()->where('email', 'like', 'crm-demo.%')->count()],
        ]);

        $bySource = HirevoLead::query()
            ->selectRaw('COALESCE(referral_source, lead_summary, \'unknown\') as src, COUNT(*) as c')
            ->groupBy('src')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        if ($bySource->isNotEmpty()) {
            $this->newLine();
            $this->info('Top lead sources:');
            foreach ($bySource as $row) {
                $this->line("  {$row->src}: {$row->c}");
            }
        }

        $recent = HirevoLead::query()
            ->with('candidate:id,name,email')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($recent->isNotEmpty()) {
            $this->newLine();
            $this->info('Latest 5 leads:');
            foreach ($recent as $lead) {
                $name = $lead->candidate?->name ?? '(no candidate)';
                $src = $lead->referral_source ?? $lead->lead_summary ?? '—';
                $this->line("  #{$lead->id} {$name} | {$src} | assigned:".($lead->assigned_to ?? 'no'));
            }
        }

        $marketing = Admin::query()->where('email', 'marketing@themesdesign.test')->first();
        if ($marketing) {
            $q = HirevoLead::query();
            $visibility->restrictVisibleLeads($q, $marketing);
            $this->newLine();
            $this->line('Marketing visible leads: '.$q->count());
        }

        $exec = Admin::query()->where('email', 'talent.executive@themesdesign.test')->first();
        if ($exec) {
            $q = HirevoLead::query();
            $visibility->restrictVisibleLeads($q, $exec);
            $this->line('Talent executive visible leads: '.$q->count());
        }

        try {
            DB::connection()->getPdo();
            $this->newLine();
            $this->info('Database connection: OK');
        } catch (\Throwable $e) {
            $this->error('Database connection failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($total === 0) {
            $this->warn('No leads in DB. On Hirevo: upload a resume or apply to a job, or run: php artisan crm:seed-demo');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Pipeline check complete. Open /leads in admin CRM.');

        return self::SUCCESS;
    }
}
