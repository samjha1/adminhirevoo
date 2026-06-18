<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leadsmanager\LeadsmanagerAdvertiser;
use App\Models\Leadsmanager\LeadsmanagerCampaign;
use App\Models\Leadsmanager\LeadsmanagerLeadFile;
use App\Services\AdsManagerLeadImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class AdsManagerLeadController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('leadsmanager_lead_files'), 503, 'Ads Manager lead file table is not available. Run leadsmanager migrations.');

        $query = LeadsmanagerLeadFile::query()
            ->with(['advertiser', 'campaign'])
            ->latest('id');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhereHas('advertiser', fn ($a) => $a->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('campaign_id');
        }

        if ($campaignId = $request->query('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        return view('admin.ads-manager.leads.index', [
            'leadFiles' => $query->paginate(25)->withQueryString(),
            'advertisers' => LeadsmanagerAdvertiser::query()->where('role', 'user')->orderBy('name')->get(),
            'campaigns' => LeadsmanagerCampaign::query()->with('advertiser')->orderBy('name')->get(),
        ]);
    }

    public function uploadBulk(Request $request, AdsManagerLeadImportService $importService): RedirectResponse
    {
        $request->merge([
            'campaign_id' => $request->input('campaign_id') ?: null,
        ]);

        $data = $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Please choose a file to upload.');

                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension() ?: '');
                    if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
                        $fail('The file must be a CSV or Excel (.xlsx) file.');
                    }
                },
            ],
            'user_id' => ['required', 'integer', 'exists:leadsmanager_users,id'],
            'campaign_id' => ['nullable', 'integer', 'exists:leadsmanager_campaigns,id'],
        ]);

        try {
            $importService->importCsv(
                $request->file('csv_file'),
                (int) $data['user_id'],
                isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withErrors(['csv_file' => $e->getMessage()])
                ->withInput($request->except('csv_file'));
        }

        return back()->with('success', 'Lead file uploaded successfully. The advertiser can download it from Ads Manager → Leads.');
    }

    public function assign(Request $request, AdsManagerLeadImportService $importService): RedirectResponse
    {
        $data = $request->validate([
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['integer', 'exists:leadsmanager_lead_files,id'],
            'campaign_id' => ['required', 'exists:leadsmanager_campaigns,id'],
        ]);

        $count = $importService->assignLeads($data['file_ids'], (int) $data['campaign_id']);
        $campaign = LeadsmanagerCampaign::query()->findOrFail($data['campaign_id']);

        return back()->with('success', "{$count} file(s) assigned to {$campaign->name}.");
    }
}
