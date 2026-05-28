<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Hirevo\HirevoCareerConsultationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();
        abort_unless($admin->hasAnyRole([AdminRole::Admin, AdminRole::Marketing]), 403);

        $q = HirevoCareerConsultationRequest::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $s = $request->string('q')->toString();
            $q->whereHas('user', function ($uq) use ($s) {
                $uq->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        return response()->json($q->paginate((int) $request->get('per_page', 15)));
    }
}
