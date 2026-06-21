<?php

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportRunController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'onboarding', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::put('/companies/{company}/verification', [CompanyController::class, 'updateVerification'])->name('companies.verification');
        Route::post('/companies/{company}/api-keys', [CompanyController::class, 'generateApiKey'])->name('companies.api-keys.generate');
        Route::post('/companies/{company}/mobile-bg-profile', [CompanyController::class, 'extractMobileProfile'])->name('companies.mobile-bg-profile');
        Route::post('/companies/{company}/mobile-bg-import', [CompanyController::class, 'importMobileListings'])->name('companies.mobile-bg-import');
        Route::get('/companies/{company}/mobile-bg-import/{run}', [CompanyController::class, 'importStatus'])->name('companies.mobile-bg-import.status');

        Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/{listing:id}/edit', [ListingController::class, 'edit'])->name('listings.edit');
        Route::put('/listings/{listing:id}', [ListingController::class, 'update'])->name('listings.update');
        Route::post('/listings/{listing:id}/archive', [ListingController::class, 'archive'])->name('listings.archive');
        Route::post('/listings/{listing:id}/publish', [ListingController::class, 'publish'])->name('listings.publish');
        Route::put('/listings/{listing:id}/status', [ListingController::class, 'updateStatus'])->name('listings.status');

        Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'revoke'])->name('api-keys.revoke');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::put('/reports/{report}', [ReportController::class, 'resolve'])->name('reports.resolve');

        Route::get('/imports', [ImportRunController::class, 'index'])->name('imports.index');
        Route::post('/imports/{import}/cancel', [ImportRunController::class, 'cancel'])->name('imports.cancel');

        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads/extract', [LeadController::class, 'store'])->name('leads.extract');
        Route::post('/leads/refresh-counts', [LeadController::class, 'refreshListingCounts'])->name('leads.refresh-counts');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::post('/leads/{lead}/send-invite', [LeadController::class, 'sendInvite'])->name('leads.send-invite');
    });