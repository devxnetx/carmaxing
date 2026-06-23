<?php

return [
    'new_ad_days' => (int) env('LISTING_NEW_AD_DAYS', 7),
    'newest_cars_days' => (int) env('LISTING_NEWEST_CARS_DAYS', 2),
    'search_history_limit' => (int) env('SEARCH_HISTORY_LIMIT', 20),
    'catalog_counts_ttl_hours' => (int) env('LISTING_CATALOG_COUNTS_TTL_HOURS', 6),
    'show_cache_ttl_hours' => (int) env('LISTING_SHOW_CACHE_TTL_HOURS', 1),
];