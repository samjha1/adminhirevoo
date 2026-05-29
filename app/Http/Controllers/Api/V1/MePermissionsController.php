<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MePermissionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'permissions' => $admin->permissionSlugs(),
            'role' => $admin->role->value,
            'crm_role' => $admin->crmRole?->slug,
        ]);
    }
}
