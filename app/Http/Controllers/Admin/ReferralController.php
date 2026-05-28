<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoReferralRequest;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService)
    {
    }

    public function index(Request $request): View
    {
        $query = HirevoReferralRequest::query()
            ->with(['candidate', 'referrer'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('admin.referrals.index', [
            'referrals' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function updateStatus(Request $request, HirevoReferralRequest $referral): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,accepted,rejected,hired,reward_paid'],
        ]);

        $this->referralService->markStatus($referral, $validated['status']);

        return back()->with('success', "Referral #{$referral->id} updated.");
    }
}

