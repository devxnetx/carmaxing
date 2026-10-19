<?php

use App\Http\Controllers\Api\BidCarsImportApiController;
use App\Http\Controllers\Api\ListingApiController;
use App\Http\Controllers\AppApi\AuthController as AppAuthController;
use App\Http\Controllers\AppApi\CatalogController as AppCatalogController;
use App\Http\Controllers\AppApi\FavoriteController as AppFavoriteController;
use App\Http\Controllers\AppApi\ListingController as AppListingController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['bid-cars.import', 'throttle:60,1'])->group(function () {
    Route::post('/bid-cars/import', [BidCarsImportApiController::class, 'store']);
});

Route::prefix('v1')->middleware(['company.api', 'throttle:company-api'])->group(function () {
    Route::get('/catalog', [ListingApiController::class, 'catalog']);
    Route::get('/listings', [ListingApiController::class, 'index']);
    Route::post('/listings', [ListingApiController::class, 'store']);
    Route::get('/listings/{id}', [ListingApiController::class, 'show']);
    Route::put('/listings/{id}', [ListingApiController::class, 'update']);
    Route::delete('/listings/{id}', [ListingApiController::class, 'archive']);
});

Route::prefix('app')->middleware([SetLocale::class, 'throttle:60,1'])->group(function () {
    Route::post('/login', [AppAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/catalog/counts', [AppCatalogController::class, 'counts']);
    Route::get('/catalog', [AppCatalogController::class, 'show']);
    Route::get('/listings', [AppListingController::class, 'index']);
    Route::get('/listings/{id}', [AppListingController::class, 'show']);
    Route::post('/listings/{id}/phone', [AppListingController::class, 'phoneClick']);
    Route::post('/listings/{id}/report', [AppListingController::class, 'report']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AppAuthController::class, 'logout']);
        Route::get('/me', [AppAuthController::class, 'me']);
        Route::get('/favorites', [AppFavoriteController::class, 'index']);
        Route::post('/listings/{id}/favorite', [AppFavoriteController::class, 'toggle']);
        Route::post('/listings/{id}/contact', [AppListingController::class, 'contact']);
    });
});