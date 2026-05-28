<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoReferralFormSubmission;
use Illuminate\View\View;

class ReferralFormSubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = HirevoReferralFormSubmission::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.referral-submissions.index', [
            'submissions' => $submissions,
        ]);
    }
}

