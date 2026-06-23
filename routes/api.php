<?php

use App\Http\Controllers\Api\BidCarsImportApiController;
use App\Http\Controllers\Api\ListingApiController;
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