<?php

use App\Support\BidCarsImportConfig;

return [

    /*
    |--------------------------------------------------------------------------
    | Shared import settings
    |--------------------------------------------------------------------------
    |
    | Brands, pages per brand, backend domain, API key, headless mode, and
    | bid.cars search filters are defined in scripts/bid-cars-worker/import.config.json
    | so Laravel and the standalone worker use the same source of truth.
    |
    |
    | Optional .env overrides:
    |   BID_CARS_BACKEND_DOMAIN=https://carmaxing.online
    |   BID_CARS_IMPORT_API_KEY=your-secret-key
    |   BID_CARS_BRANDS=Audi,BMW,Mercedes-Benz
    |   BID_CARS_PAGES_PER_BRAND=3
    |   BID_CARS_PAGES_PER_BRAND=full
    |   BID_CARS_HEADLESS=0
    |
    */

    'import_config_path' => BidCarsImportConfig::path(),

    'cookie' => env('BID_CARS_COOKIE'),

    'import_api_key' => env('BID_CARS_IMPORT_API_KEY'),

];