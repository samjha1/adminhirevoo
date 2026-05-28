<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leadsmanager\LeadsmanagerAd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SponsoredAdController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Schema::hasTable('leadsmanager_ads'), 503, 'Ads Manager tables are not available.');

        $status = $request->query('status', 'pending_review');

        $query = LeadsmanagerAd::query()
            ->with(['campaign', 'advertiser'])
            ->orderByDesc('updated_at');

        if ($status && $status !== 'all' && in_array($status, LeadsmanagerAd::STATUSES, true)) {
            $query->where('status', $status);
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

        $pendingCount = LeadsmanagerAd::where('status', 'pending_review')->count();

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
        if ($ad->campaign && $ad->campaign->status !== 'active') {
            return back()->with('error', 'Activate the advertiser’s campaign in Ads Manager before approving this ad.');
        }

        $ad->update(['status' => 'active']);

        return redirect()
            ->route('admin.sponsored-ads.index', ['status' => 'active'])
            ->with('success', "“{$ad->name}” approved — now live on Hirevo ({$ad->placementLabel()}).");
    }

    public function reject(LeadsmanagerAd $ad): RedirectResponse
    {
        $ad->update(['status' => 'draft']);

        return back()->with('success', "“{$ad->name}” sent back to draft.");
    }

    public function pause(LeadsmanagerAd $ad): RedirectResponse
    {
        $ad->update(['status' => 'paused']);

        return back()->with('success', "“{$ad->name}” paused — hidden on Hirevo.");
    }
}
