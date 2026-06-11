<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoPayment;
use App\Services\EmployerPlanPaymentService;
use App\Services\EmployerPlanPaymentVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerPlanPaymentController extends Controller
{
    public function __construct(
        private readonly EmployerPlanPaymentVisibilityService $visibility,
        private readonly EmployerPlanPaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $admin = $request->user('admin');
        abort_unless($this->visibility->canAccessList($admin), 403);

        $status = $request->string('status')->toString();
        $allowedStatuses = ['pending', 'completed', ''];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $query = HirevoPayment::query()
            ->employerPlanCheckout()
            ->with(['user.referrerProfile'])
            ->orderByDesc('created_at');

        $this->visibility->restrictVisible($query, $admin);

        if ($status !== '') {
            $query->where('status', $status);
        }

        $payments = $query->paginate(20)->withQueryString();

        $countsQuery = HirevoPayment::query()->employerPlanCheckout();

        $this->visibility->restrictVisible($countsQuery, $admin);

        $pendingCount = (clone $countsQuery)->where('status', HirevoPayment::STATUS_PENDING)->count();
        $completedCount = (clone $countsQuery)->where('status', HirevoPayment::STATUS_COMPLETED)->count();

        return view('admin.payments.employer-plans', [
            'payments' => $payments,
            'status' => $status,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
            'canComplete' => $admin->canPermission('employer_payments.complete'),
        ]);
    }

    public function complete(Request $request, HirevoPayment $payment): RedirectResponse
    {
        $admin = $request->user('admin');
        abort_unless($admin->canPermission('employer_payments.complete'), 403);
        abort_unless($this->visibility->canView($admin, $payment), 403);

        if (! $payment->isEmployerPlanCheckout()) {
            abort(404);
        }

        try {
            $this->payments->completePayment($payment);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $planName = (string) ($payment->fresh()->meta['plan_name'] ?? 'plan');

        $method = $payment->payment_gateway === HirevoPayment::GATEWAY_NETBANKING ? 'Net banking payment' : 'Cheque';

        return back()->with('success', "{$method} verified. {$planName} subscription activated for the employer.");
    }
}
