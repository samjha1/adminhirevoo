<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leadsmanager\LeadsmanagerAd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SponsoredAdController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('leadsmanager_ads'), 503, 'Ads Manager tables are not available.');

        $status = $request->query('status', 'under_review');

        $query = LeadsmanagerAd::query()
            ->with(['campaign', 'advertiser'])
            ->orderByDesc('updated_at');

        if ($status && $status !== 'all') {
            if ($status === 'under_review') {
                $query->whereIn('status', LeadsmanagerAd::REVIEW_STATUSES);
            } elseif (in_array($status, LeadsmanagerAd::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%");
            });
        }

        if ($placement = $request->query('placement')) {
            $query->where('placement', $placement);
        }

        $pendingCount = LeadsmanagerAd::query()
            ->whereIn('status', LeadsmanagerAd::REVIEW_STATUSES)
            ->count();

        return view('admin.sponsored-ads.index', [
            'ads' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'pendingCount' => $pendingCount,
            'placements' => LeadsmanagerAd::PLACEMENTS,
        ]);
    }

    public function show(LeadsmanagerAd $ad): View
    {
        $ad->load(['campaign', 'advertiser']);

        return view('admin.sponsored-ads.show', compact('ad'));
    }

    public function approve(LeadsmanagerAd $ad): RedirectResponse
    {
        if (! $ad->isPendingReview() && $ad->status !== 'approved') {
            return back()->with('error', 'Only ads under review can be approved.');
        }

        if ($ad->campaign && in_array($ad->campaign->status, ['completed', 'rejected'], true)) {
            return back()->with('error', 'This campaign is completed or rejected and cannot go live.');
        }

        $campaignActivated = false;

        DB::transaction(function () use ($ad, &$campaignActivated): void {
            if ($ad->campaign && $ad->campaign->status !== 'active') {
                $ad->campaign->update([
                    'status' => 'active',
                    'approved_at' => $ad->campaign->approved_at ?? now(),
                ]);
                $campaignActivated = true;
            }

            $ad->update([
                'status' => 'active',
                'rejection_reason' => null,
            ]);
        });

        $message = "“{$ad->name}” approved — now live on Hirevo ({$ad->placementLabel()}).";
        if ($campaignActivated && $ad->campaign) {
            $message .= " Campaign “{$ad->campaign->name}” was activated.";
        }

        return redirect()
            ->route('admin.sponsored-ads.index', ['status' => 'active'])
            ->with('success', $message);
    }

    public function reject(Request $request, LeadsmanagerAd $ad): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
        ]);

        return back()->with('success', "“{$ad->name}” rejected.");
    }

    public function pause(LeadsmanagerAd $ad): RedirectResponse
    {
        $ad->update(['status' => 'paused']);

        return back()->with('success', "“{$ad->name}” paused — hidden on Hirevo.");
    }
}
