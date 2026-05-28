<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = HirevoPayment::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $payments = $query->paginate(20)->withQueryString();
        $revenue = (float) HirevoPayment::query()->where('status', 'completed')->sum('amount');

        return view('admin.payments.index', [
            'payments' => $payments,
            'revenue' => $revenue,
        ]);
    }
}

