<?php

use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Dashboard\DashboardExportController;
use App\Http\Controllers\Admin\Settings\AuditLogController;
use App\Http\Controllers\Admin\EmployerController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\Leads\LeadCallController;
use App\Http\Controllers\Admin\Leads\LeadController;
use App\Http\Controllers\Admin\Leads\LeadFollowUpController;
use App\Http\Controllers\Admin\Leads\CompanyFollowUpController;
use App\Http\Controllers\Admin\Leads\EmployerPipelineController;
use App\Http\Controllers\Admin\Leads\LeadKanbanController;
use App\Http\Controllers\Admin\Leads\StandaloneLeadController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\EmployerPlanPaymentController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReferralFormSubmissionController;
use App\Http\Controllers\Admin\Settings\RbacSettingsController;
use App\Http\Controllers\Admin\SponsoredAdController;
use App\Http\Controllers\Admin\Staff\AdminStaffController;
use App\Http\Controllers\Admin\Users\HirevoUserController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Modules\Leads\Services\HirevoLeadExportService;
use App\Support\AdminHomeResolver;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth('admin')->check()) {
        return redirect(AdminHomeResolver::urlFor(auth('admin')->user()));
    }

    return redirect()->route('admin.login');
})->middleware('admin.guard');

Route::middleware('guest:admin')->group(function () {
    Route::view('/login', 'auth.login')->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('admin.login.store');
});

Route::post('/logout', [\App\Http\Controllers\Auth\AdminAuthController::class, 'destroy'])
    ->middleware('auth:admin')
    ->name('admin.logout');

Route::middleware(['admin.guard', 'auth:admin', 'permission:analytics.view'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/export', DashboardExportController::class)
        ->middleware('permission:analytics.export')
        ->name('admin.dashboard.export');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:audit.view'])->group(function () {
    Route::get('/settings/audit-logs', [AuditLogController::class, 'index'])->name('admin.settings.audit-logs');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:leads.view', 'sales.pipeline:candidate'])->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('admin.leads.index');
    Route::get('/leads/export', fn (\Illuminate\Http\Request $request, HirevoLeadExportService $export) => $export->export($request, $request->user('admin')))
        ->middleware('permission:leads.export')
        ->name('admin.leads.export');
    Route::post('/leads/bulk/assign-manager', [LeadController::class, 'bulkAssignManagers'])
        ->middleware(['permission:leads.assign_manager', 'throttle:30,1'])
        ->name('admin.leads.bulk-assign-manager');
    Route::post('/leads/bulk/assign-employee', [LeadController::class, 'bulkAssignEmployees'])
        ->middleware(['permission:leads.assign_employee', 'throttle:30,1'])
        ->name('admin.leads.bulk-assign-employee');
    Route::get('/leads/kanban', [LeadKanbanController::class, 'index'])
        ->middleware('permission:kanban.view')
        ->name('admin.leads.kanban');
    Route::post('/leads/{lead}/kanban-stage', [LeadKanbanController::class, 'moveStage'])
        ->middleware('permission:kanban.view')
        ->name('admin.leads.kanban-stage');
    Route::get('/leads/{lead}', [LeadController::class, 'showLead'])->name('admin.leads.show');
    Route::post('/leads/{lead}/stage', [LeadController::class, 'updateStage'])
        ->middleware('permission:leads.update_stage')
        ->name('admin.leads.stage');
    Route::post('/leads/{lead}/sales-status', [LeadController::class, 'updateSalesStatus'])
        ->middleware('permission:leads.update_sales_status')
        ->name('admin.leads.sales-status');
    Route::post('/leads/{lead}/assign-manager', [LeadController::class, 'assignManager'])
        ->middleware(['permission:leads.assign_manager', 'throttle:60,1'])
        ->name('admin.leads.assign-manager');
    Route::post('/leads/{lead}/reassign-manager', [LeadController::class, 'reassignManager'])
        ->middleware(['permission:leads.reassign', 'throttle:60,1'])
        ->name('admin.leads.reassign-manager');
    Route::post('/leads/{lead}/release', [LeadController::class, 'releaseToPool'])
        ->middleware(['permission:leads.release', 'throttle:60,1'])
        ->name('admin.leads.release');
    Route::post('/leads/{lead}/assign-employee', [LeadController::class, 'assignEmployee'])
        ->middleware(['permission:leads.assign_employee', 'throttle:60,1'])
        ->name('admin.leads.assign-employee');
    Route::post('/leads/{lead}/reassign-employee', [LeadController::class, 'reassignEmployee'])
        ->middleware(['permission:leads.reassign', 'throttle:60,1'])
        ->name('admin.leads.reassign-employee');
    Route::post('/leads/{lead}/take-back', [LeadController::class, 'takeBack'])
        ->middleware(['permission:leads.take_back', 'throttle:60,1'])
        ->name('admin.leads.take-back');
    Route::post('/leads/{lead}/calls', [LeadCallController::class, 'store'])
        ->middleware('permission:leads.log_call')
        ->name('admin.leads.calls.store');
    Route::post('/leads/{lead}/follow-ups', [LeadFollowUpController::class, 'store'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.leads.follow-ups.store');
    Route::get('/follow-ups', [LeadFollowUpController::class, 'myFollowUps'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.follow-ups.index');
    Route::get('/follow-ups/today', [LeadFollowUpController::class, 'today'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.follow-ups.today');
    Route::post('/follow-ups/{followUp}/complete', [LeadFollowUpController::class, 'complete'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.follow-ups.complete');
    Route::get('/applied-jobs', [ApplicationController::class, 'index'])
        ->middleware('permission:applications.view')
        ->name('admin.applications.index');
    Route::get('/applied-jobs/{application}', [ApplicationController::class, 'show'])
        ->middleware('permission:applications.view')
        ->name('admin.applications.show');
    Route::post('/applied-jobs/{application}/status', [ApplicationController::class, 'updateStatus'])
        ->middleware('permission:applications.view')
        ->name('admin.applications.status');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:leads.view', 'sales.pipeline:employer'])->group(function () {
    Route::get('/pipelines/companies', [EmployerPipelineController::class, 'index'])->name('admin.employers.pipeline.index');
    Route::get('/pipelines/companies/kanban', [EmployerPipelineController::class, 'kanban'])
        ->middleware('permission:kanban.view')
        ->name('admin.employers.pipeline.kanban');
    Route::get('/pipelines/companies/{prospect}', [EmployerPipelineController::class, 'show'])->name('admin.employers.pipeline.show');
    Route::post('/pipelines/companies/{prospect}/stage', [EmployerPipelineController::class, 'updateStage'])
        ->middleware('permission:leads.update_stage')
        ->name('admin.employers.pipeline.stage');
    Route::post('/pipelines/companies/{prospect}/follow-ups', [EmployerPipelineController::class, 'storeFollowUp'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.employers.follow-ups.store');
    Route::post('/pipelines/companies/{prospect}/meetings', [EmployerPipelineController::class, 'storeMeeting'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.employers.meetings.store');
    Route::post('/meetings/{meeting}/complete', [CompanyFollowUpController::class, 'completeMeeting'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.companies.meetings.complete');
    Route::get('/follow-ups/companies', [CompanyFollowUpController::class, 'index'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.companies.follow-ups.index');
    Route::get('/follow-ups/companies/today', [CompanyFollowUpController::class, 'today'])
        ->middleware('permission:leads.manage_followups')
        ->name('admin.companies.follow-ups.today');
    Route::post('/pipelines/companies/bulk/assign-manager', [EmployerPipelineController::class, 'bulkAssignManagers'])
        ->middleware(['permission:leads.assign_manager', 'throttle:30,1'])
        ->name('admin.employers.pipeline.bulk-assign-manager');
    Route::post('/pipelines/companies/bulk/assign-employee', [EmployerPipelineController::class, 'bulkAssignEmployees'])
        ->middleware(['permission:leads.assign_employee', 'throttle:30,1'])
        ->name('admin.employers.pipeline.bulk-assign-employee');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:employer_payments.view'])->group(function () {
    Route::get('/employer-plan-payments', [EmployerPlanPaymentController::class, 'index'])
        ->name('admin.employer-plan-payments.index');
    Route::post('/employer-plan-payments/{payment}/complete', [EmployerPlanPaymentController::class, 'complete'])
        ->middleware('permission:employer_payments.complete')
        ->name('admin.employer-plan-payments.complete');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:consultations.view'])->group(function () {
    Route::get('/career-consultations', [LeadController::class, 'consultations'])->name('admin.consultations.index');
    Route::get('/career-consultations/{consultation}', [LeadController::class, 'showConsultation'])->name('admin.consultations.show');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:leads.import|leads.create'])->group(function () {
    Route::get('/marketing-leads', [StandaloneLeadController::class, 'index'])->name('admin.standalone-leads.index');
    Route::get('/marketing-leads/create', [StandaloneLeadController::class, 'create'])
        ->middleware('permission:leads.create')
        ->name('admin.standalone-leads.create');
    Route::post('/marketing-leads', [StandaloneLeadController::class, 'store'])
        ->middleware('permission:leads.create')
        ->name('admin.standalone-leads.store');
    Route::get('/marketing-leads/import', [StandaloneLeadController::class, 'importForm'])
        ->middleware('permission:leads.import')
        ->name('admin.standalone-leads.import');
    Route::post('/marketing-leads/import', [StandaloneLeadController::class, 'import'])
        ->middleware(['permission:leads.import', 'throttle:10,1'])
        ->name('admin.standalone-leads.import.store');
    Route::get('/marketing-leads/export', [StandaloneLeadController::class, 'export'])
        ->middleware('permission:leads.export')
        ->name('admin.standalone-leads.export');
    Route::get('/marketing-leads/template', [StandaloneLeadController::class, 'template'])
        ->middleware('permission:leads.import')
        ->name('admin.standalone-leads.template');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:staff.view|staff.manage'])->group(function () {
    Route::get('/staff', [AdminStaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/staff/create', [AdminStaffController::class, 'create'])
        ->middleware('permission:staff.manage')
        ->name('admin.staff.create');
    Route::post('/staff', [AdminStaffController::class, 'store'])
        ->middleware('permission:staff.manage')
        ->name('admin.staff.store');
    Route::get('/staff/{staff}/edit', [AdminStaffController::class, 'edit'])
        ->middleware('permission:staff.manage')
        ->name('admin.staff.edit');
    Route::put('/staff/{staff}', [AdminStaffController::class, 'update'])
        ->middleware('permission:staff.manage')
        ->name('admin.staff.update');
    Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])
        ->middleware('permission:staff.manage')
        ->name('admin.staff.destroy');
});

Route::middleware(['admin.guard', 'auth:admin', 'permission:rbac.manage_permissions'])->group(function () {
    Route::get('/settings/roles', [RbacSettingsController::class, 'index'])->name('admin.settings.rbac');
    Route::put('/settings/roles/{role}', [RbacSettingsController::class, 'update'])->name('admin.settings.rbac.update');
});

Route::middleware(['admin.guard', 'auth:admin', 'role:admin|super_admin'])->group(function () {
    Route::get('/users', [HirevoUserController::class, 'index'])
        ->middleware('permission:platform.users')
        ->name('admin.users.index');
    Route::post('/users/{user}/status', [HirevoUserController::class, 'updateStatus'])
        ->middleware('permission:platform.users')
        ->name('admin.users.status');

    Route::get('/employers', [EmployerController::class, 'index'])
        ->middleware('permission:platform.employers')
        ->name('admin.employers.index');
    Route::get('/employers/{employer}', [EmployerController::class, 'show'])
        ->middleware('permission:platform.employers')
        ->name('admin.employers.show');
    Route::post('/employers/{employer}/approve', [EmployerController::class, 'approve'])
        ->middleware('permission:platform.employers')
        ->name('admin.employers.approve');
    Route::post('/employers/{employer}/reject', [EmployerController::class, 'reject'])
        ->middleware('permission:platform.employers')
        ->name('admin.employers.reject');

    Route::get('/jobs', [JobController::class, 'index'])
        ->middleware('permission:platform.jobs')
        ->name('admin.jobs.index');
    Route::post('/jobs/{job}/status', [JobController::class, 'updateStatus'])
        ->middleware('permission:platform.jobs')
        ->name('admin.jobs.status');

    Route::get('/referrals', [ReferralController::class, 'index'])
        ->middleware('permission:platform.referrals')
        ->name('admin.referrals.index');
    Route::post('/referrals/{referral}/status', [ReferralController::class, 'updateStatus'])
        ->middleware('permission:platform.referrals')
        ->name('admin.referrals.status');
    Route::get('/payments', [PaymentController::class, 'index'])
        ->middleware('permission:platform.payments')
        ->name('admin.payments.index');
    Route::get('/referral-submissions', [ReferralFormSubmissionController::class, 'index'])
        ->middleware('permission:platform.referrals')
        ->name('admin.referral-submissions.index');

    Route::get('/sponsored-ads', [SponsoredAdController::class, 'index'])
        ->middleware('permission:platform.sponsored_ads')
        ->name('admin.sponsored-ads.index');
    Route::get('/sponsored-ads/{ad}', [SponsoredAdController::class, 'show'])
        ->middleware('permission:platform.sponsored_ads')
        ->name('admin.sponsored-ads.show');
    Route::post('/sponsored-ads/{ad}/approve', [SponsoredAdController::class, 'approve'])
        ->middleware('permission:platform.sponsored_ads')
        ->name('admin.sponsored-ads.approve');
    Route::post('/sponsored-ads/{ad}/reject', [SponsoredAdController::class, 'reject'])
        ->middleware('permission:platform.sponsored_ads')
        ->name('admin.sponsored-ads.reject');
    Route::post('/sponsored-ads/{ad}/pause', [SponsoredAdController::class, 'pause'])
        ->middleware('permission:platform.sponsored_ads')
        ->name('admin.sponsored-ads.pause');
});
