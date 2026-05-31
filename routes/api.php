<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\ConsultationApiController;
use App\Http\Controllers\Api\V1\LeadApiController;
use App\Http\Controllers\Api\V1\LeadCallApiController;
use App\Http\Controllers\Api\V1\LeadFollowUpApiController;
use App\Http\Controllers\Api\V1\LeadTimelineApiController;
use App\Http\Controllers\Api\V1\MePermissionsController;
use App\Http\Controllers\Api\V1\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me/permissions', MePermissionsController::class);

        Route::prefix('dashboard')->middleware('permission:analytics.view')->group(function () {
            Route::get('/summary', [DashboardApiController::class, 'summary']);
            Route::get('/revenue', [DashboardApiController::class, 'revenue']);
            Route::get('/leads', [DashboardApiController::class, 'leads']);
            Route::get('/funnel', [DashboardApiController::class, 'funnel']);
            Route::get('/team-performance', [DashboardApiController::class, 'teamPerformance']);
            Route::get('/manager-performance', [DashboardApiController::class, 'managerPerformance']);
            Route::get('/employee-performance', [DashboardApiController::class, 'employeePerformance']);
            Route::get('/recent-activities', [DashboardApiController::class, 'recentActivities']);
            Route::get('/export/excel', [DashboardApiController::class, 'exportExcel'])
                ->middleware('permission:analytics.export');
            Route::get('/export/pdf', [DashboardApiController::class, 'exportPdf'])
                ->middleware('permission:analytics.export');
        });

        Route::get('/users', [StaffUserController::class, 'index'])
            ->middleware('permission:staff.view|staff.manage');
        Route::post('/users', [StaffUserController::class, 'store'])
            ->middleware('permission:staff.manage');
        Route::patch('/users/{staff}/role', [StaffUserController::class, 'assignRole'])
            ->middleware('permission:staff.manage');

        Route::get('/leads', [LeadApiController::class, 'index'])
            ->middleware('permission:leads.view');
        Route::post('/leads/{lead}/assign', [LeadApiController::class, 'assign'])
            ->middleware('permission:leads.assign_manager|leads.assign_employee');
        Route::post('/leads/{lead}/unassign', [LeadApiController::class, 'unassign'])
            ->middleware('permission:leads.release');
        Route::post('/leads/{lead}/reassign', [LeadApiController::class, 'reassign'])
            ->middleware('permission:leads.reassign');
        Route::patch('/leads/{lead}/status', [LeadApiController::class, 'updateStatus'])
            ->middleware('permission:leads.update_sales_status');

        Route::get('/leads/{lead}/calls', [LeadCallApiController::class, 'index'])
            ->middleware('permission:leads.log_call');
        Route::post('/leads/{lead}/calls', [LeadCallApiController::class, 'store'])
            ->middleware('permission:leads.log_call');

        Route::get('/leads/{lead}/follow-ups', [LeadFollowUpApiController::class, 'index'])
            ->middleware('permission:leads.manage_followups');
        Route::post('/leads/{lead}/follow-ups', [LeadFollowUpApiController::class, 'store'])
            ->middleware('permission:leads.manage_followups');
        Route::patch('/leads/{lead}/follow-ups/{followUp}', [LeadFollowUpApiController::class, 'update'])
            ->middleware('permission:leads.manage_followups');

        Route::get('/leads/{lead}/timeline', LeadTimelineApiController::class)
            ->middleware('permission:leads.view');

        Route::get('/consultations', [ConsultationApiController::class, 'index'])
            ->middleware('permission:consultations.view');
    });
});
