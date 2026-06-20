<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsApiPlaygroundController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ListingContactController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingPhoneClickController;
use App\Http\Controllers\ListingReportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MobileBgImportController;
use App\Http\Controllers\MobileBgProfileController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SearchHistoryController;
use App\Http\Controllers\MyTenderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TenderBidController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\TenderRulesController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'xml'])->name('sitemap');
Route::get('/sitemap', [SitemapController::class, 'html'])->name('sitemap.page');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/api/search/map-markers', [SearchController::class, 'mapMarkers'])->name('search.map-markers');
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/{listing:slug}/add', [CompareController::class, 'add'])->name('compare.add');
Route::post('/compare/{listing:slug}/remove', [CompareController::class, 'remove'])->name('compare.remove');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');
Route::get('/compare/state', [CompareController::class, 'state'])->name('compare.state');
Route::get('/api/brands/{brand}/models', [SearchController::class, 'models'])->name('brands.models');
Route::get('/api/brands/{brand}/model-tree', [SearchController::class, 'modelTree'])->name('brands.model-tree');
Route::get('/api/regions/{region}/cities', [SearchController::class, 'cities'])->name('regions.cities');

Route::get('/dealers/{company:slug}', [CompanyController::class, 'show'])->name('company.show');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/docs/api', [DocsController::class, 'api'])->name('docs.api');
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');
Route::post('/cookie-consent', [CookieConsentController::class, 'store'])->name('cookie-consent.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.callback');
});

Route::post('/logout', [SocialAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'onboarding'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
    Route::post('/saved-searches/{savedSearch}/toggle-alert', [SavedSearchController::class, 'toggleAlert'])->name('saved-searches.toggle-alert');
    Route::get('/search-history', [SearchHistoryController::class, 'index'])->name('search-history.index');
    Route::delete('/search-history/{searchHistory}', [SearchHistoryController::class, 'destroy'])->name('search-history.destroy');
    Route::delete('/search-history', [SearchHistoryController::class, 'destroyAll'])->name('search-history.destroy-all');

    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::post('/listings/{listing}/archive', [ListingController::class, 'archive'])->name('listings.archive');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/api-keys', [SettingsController::class, 'generateApiKey'])->name('settings.api-keys.generate');
    Route::delete('/settings/api-keys/{apiKey}', [SettingsController::class, 'revokeApiKey'])->name('settings.api-keys.revoke');

    Route::post('/docs/api/try', [DocsApiPlaygroundController::class, 'run'])->name('docs.api.try');
    Route::get('/docs/api/sample-listing', [DocsApiPlaygroundController::class, 'sampleListingId'])->name('docs.api.sample-listing');

    Route::get('/company/edit', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
    Route::post('/company/mobile-bg-profile', [MobileBgProfileController::class, 'extract'])->name('company.mobile-bg-profile');
    Route::post('/company/mobile-bg-import', [MobileBgImportController::class, 'store'])->name('company.mobile-bg-import');
    Route::get('/company/mobile-bg-import/{run}', [MobileBgImportController::class, 'status'])->name('company.mobile-bg-import.status');

    Route::middleware('tenders.enabled')->prefix('my/tenders')->name('my.tenders.')->group(function () {
        Route::get('/', [MyTenderController::class, 'index'])->name('index');
        Route::get('/create', [MyTenderController::class, 'create'])->name('create');
        Route::post('/', [MyTenderController::class, 'store'])->name('store');
        Route::get('/{tender:id}/manage', [MyTenderController::class, 'manage'])->name('manage');
        Route::post('/{tender:id}/bids/{bid}/award', [MyTenderController::class, 'award'])->name('award');
    });
});

Route::middleware('tenders.enabled')->prefix('tenders')->name('tenders.')->group(function () {
    Route::get('/', [TenderController::class, 'index'])->name('index');
    Route::get('/{tender:slug}/state', [TenderController::class, 'state'])->name('state');

    Route::middleware('auth')->group(function () {
        Route::post('/accept-rules', [TenderRulesController::class, 'accept'])->name('accept-rules');
        Route::post('/{tender:slug}/bids', [TenderBidController::class, 'store'])->name('bids.store');
        Route::delete('/{tender:slug}/bids/{bid}', [TenderBidController::class, 'destroy'])->name('bids.destroy');
    });

    Route::get('/{tender:slug}', [TenderController::class, 'show'])->name('show');
});

Route::get('/listings/{listing:slug}', [ListingController::class, 'show'])
    ->where('listing', '^(?!create$|edit$).+')
    ->name('listings.show');
Route::post('/listings/{listing:slug}/favorite', [FavoriteController::class, 'toggle'])->name('listings.favorite');
Route::post('/listings/{listing:slug}/contact', [ListingContactController::class, 'store'])->middleware('auth')->name('listings.contact');
Route::post('/listings/{listing:slug}/report', [ListingReportController::class, 'store'])->name('listings.report');
Route::post('/listings/{listing:slug}/phone-click', [ListingPhoneClickController::class, 'store'])->name('listings.phone-click');

require __DIR__.'/admin.php';