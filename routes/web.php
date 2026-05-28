<?php

use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\EmployerController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\Leads\LeadController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReferralFormSubmissionController;
use App\Http\Controllers\Admin\SponsoredAdController;
use App\Http\Controllers\Admin\Staff\AdminStaffController;
use App\Http\Controllers\Admin\Users\HirevoUserController;
use App\Http\Controllers\Admin\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest:admin')->group(function () {
    Route::view('/login', 'auth.login')->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('admin.login.store');
});

Route::post('/logout', [\App\Http\Controllers\Auth\AdminAuthController::class, 'destroy'])
    ->middleware('auth:admin')
    ->name('admin.logout');

Route::middleware(['admin.guard', 'auth:admin', 'role:admin|marketing|sales_manager|sales_employee'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/leads', [LeadController::class, 'index'])->name('admin.leads.index');
    Route::get('/career-consultations', [LeadController::class, 'consultations'])->name('admin.consultations.index');
    Route::post('/leads/bulk/assign-manager', [LeadController::class, 'bulkAssignManagers'])
        ->middleware('throttle:30,1')
        ->name('admin.leads.bulk-assign-manager');
    Route::post('/leads/bulk/assign-employee', [LeadController::class, 'bulkAssignEmployees'])
        ->middleware('throttle:30,1')
        ->name('admin.leads.bulk-assign-employee');
    Route::get('/leads/{lead}', [LeadController::class, 'showLead'])->name('admin.leads.show');
    Route::post('/leads/{lead}/stage', [LeadController::class, 'updateStage'])->name('admin.leads.stage');
    Route::post('/leads/{lead}/sales-status', [LeadController::class, 'updateSalesStatus'])->name('admin.leads.sales-status');
    Route::post('/leads/{lead}/assign-manager', [LeadController::class, 'assignManager'])->name('admin.leads.assign-manager');
    Route::post('/leads/{lead}/reassign-manager', [LeadController::class, 'reassignManager'])->name('admin.leads.reassign-manager');
    Route::post('/leads/{lead}/release', [LeadController::class, 'releaseToPool'])->name('admin.leads.release');
    Route::post('/leads/{lead}/assign-employee', [LeadController::class, 'assignEmployee'])->name('admin.leads.assign-employee');
    Route::post('/leads/{lead}/reassign-employee', [LeadController::class, 'reassignEmployee'])->name('admin.leads.reassign-employee');
    Route::post('/leads/{lead}/take-back', [LeadController::class, 'takeBack'])->name('admin.leads.take-back');
    Route::get('/career-consultations/{consultation}', [LeadController::class, 'showConsultation'])->name('admin.consultations.show');
    Route::get('/applied-jobs', [ApplicationController::class, 'index'])->name('admin.applications.index');
    Route::get('/applied-jobs/{application}', [ApplicationController::class, 'show'])->name('admin.applications.show');
    Route::post('/applied-jobs/{application}/status', [ApplicationController::class, 'updateStatus'])->name('admin.applications.status');
});

Route::middleware(['admin.guard', 'auth:admin', 'role:admin|sales_manager'])->group(function () {
    Route::get('/staff', [AdminStaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/staff/create', [AdminStaffController::class, 'create'])->name('admin.staff.create');
    Route::post('/staff', [AdminStaffController::class, 'store'])->name('admin.staff.store');
    Route::get('/staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('admin.staff.edit');
    Route::put('/staff/{staff}', [AdminStaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])->name('admin.staff.destroy');
});

Route::middleware(['admin.guard', 'auth:admin', 'role:admin'])->group(function () {
    Route::get('/users', [HirevoUserController::class, 'index'])->name('admin.users.index');
    Route::post('/users/{user}/status', [HirevoUserController::class, 'updateStatus'])->name('admin.users.status');

    Route::get('/employers', [EmployerController::class, 'index'])->name('admin.employers.index');
    Route::get('/employers/{employer}', [EmployerController::class, 'show'])->name('admin.employers.show');
    Route::post('/employers/{employer}/approve', [EmployerController::class, 'approve'])->name('admin.employers.approve');
    Route::post('/employers/{employer}/reject', [EmployerController::class, 'reject'])->name('admin.employers.reject');

    Route::get('/jobs', [JobController::class, 'index'])->name('admin.jobs.index');
    Route::post('/jobs/{job}/status', [JobController::class, 'updateStatus'])->name('admin.jobs.status');

    Route::get('/referrals', [ReferralController::class, 'index'])->name('admin.referrals.index');
    Route::post('/referrals/{referral}/status', [ReferralController::class, 'updateStatus'])->name('admin.referrals.status');
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/referral-submissions', [ReferralFormSubmissionController::class, 'index'])->name('admin.referral-submissions.index');

    Route::get('/sponsored-ads', [SponsoredAdController::class, 'index'])->name('admin.sponsored-ads.index');
    Route::get('/sponsored-ads/{ad}', [SponsoredAdController::class, 'show'])->name('admin.sponsored-ads.show');
    Route::post('/sponsored-ads/{ad}/approve', [SponsoredAdController::class, 'approve'])->name('admin.sponsored-ads.approve');
    Route::post('/sponsored-ads/{ad}/reject', [SponsoredAdController::class, 'reject'])->name('admin.sponsored-ads.reject');
    Route::post('/sponsored-ads/{ad}/pause', [SponsoredAdController::class, 'pause'])->name('admin.sponsored-ads.pause');
});
