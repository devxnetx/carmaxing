<?php

return [
    'default_bid_increment' => (int) env('TENDER_BID_INCREMENT', 100),
    'allowed_bid_increments' => [50, 100, 200, 250, 500, 1000],
    'min_duration_days' => (int) env('TENDER_MIN_DURATION_DAYS', 1),
    'max_duration_days' => (int) env('TENDER_MAX_DURATION_DAYS', 14),
    'poll_interval_ms' => (int) env('TENDER_POLL_INTERVAL_MS', 15000),
    'poll_interval_final_day_ms' => (int) env('TENDER_POLL_INTERVAL_FINAL_DAY_MS', 5000),
    'final_day_hours' => (int) env('TENDER_FINAL_DAY_HOURS', 24),
    'rules_version' => env('TENDER_RULES_VERSION', '1'),
];