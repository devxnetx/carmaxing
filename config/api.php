<?php

return [
    'requests_per_minute' => (int) env('API_REQUESTS_PER_MINUTE', 60),
    'listings_per_day' => (int) env('API_LISTINGS_PER_DAY', 200),
    'max_per_page' => (int) env('API_MAX_PER_PAGE', 50),
    'base_url' => env('APP_URL', 'http://localhost').'/api/v1',
];