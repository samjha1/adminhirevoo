<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConsultationApiController;
use App\Http\Controllers\Api\V1\LeadApiController;
use App\Http\Controllers\Api\V1\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/users', [StaffUserController::class, 'index']);
        Route::post('/users', [StaffUserController::class, 'store']);
        Route::patch('/users/{staff}/role', [StaffUserController::class, 'assignRole']);

        Route::get('/leads', [LeadApiController::class, 'index']);
        Route::post('/leads/{lead}/assign', [LeadApiController::class, 'assign']);
        Route::post('/leads/{lead}/unassign', [LeadApiController::class, 'unassign']);
        Route::post('/leads/{lead}/reassign', [LeadApiController::class, 'reassign']);
        Route::patch('/leads/{lead}/status', [LeadApiController::class, 'updateStatus']);

        Route::get('/consultations', [ConsultationApiController::class, 'index']);
    });
});
