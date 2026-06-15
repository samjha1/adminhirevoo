<?php

namespace App\Services;

use App\Models\Hirevo\HirevoUser;
use App\Support\PortalDateFilter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateExportService
{
    public function __construct(
        private readonly CandidateSectorService $sectors,
    ) {
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFilter = PortalDateFilter::fromRequest($request);
        $category = $request->string('sector')->toString();

        $query = HirevoUser::query()
            ->where('role', 'candidate')
            ->with(['candidateProfile', 'leads.jobRole', 'jobApplications.jobRole'])
            ->orderByDesc('created_at');

        $dateFilter->apply($query);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('candidateProfile', function ($pq) use ($search) {
                        $pq->where('skills', 'like', "%{$search}%")
                            ->orWhere('headline', 'like', "%{$search}%");
                    });
            });
        }

        if ($category !== '') {
            $this->sectors->applyCandidateFilter($query, $category);
        }

        $suffix = $category !== '' && $category !== 'all' ? '-'.$category : '';
        $filename = 'candidates'.$suffix.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'name', 'email', 'phone', 'sector', 'preferred_role',
                'experience_years', 'location', 'skills', 'registered_at',
            ]);

            $query->chunk(200, function ($candidates) use ($out): void {
                foreach ($candidates as $candidate) {
                    $profile = $candidate->candidateProfile;
                    $sectorKey = $this->sectors->resolveForCandidate($candidate);

                    fputcsv($out, [
                        $candidate->id,
                        $candidate->name,
                        $candidate->email,
                        $candidate->phone,
                        $this->sectors->labelForCategory($sectorKey),
                        $profile?->preferred_job_role,
                        $profile?->experience_years,
                        $profile?->location,
                        $profile?->skills,
                        $candidate->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
