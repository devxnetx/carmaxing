<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #0f172a;">
    <p>{{ __('messages.saved_search_alert_greeting', ['name' => $savedSearch->user->name]) }}</p>
    <p>{{ __('messages.saved_search_alert_body', [
        'name' => $savedSearch->name,
        'new' => $newMatches,
        'total' => $totalMatches,
    ]) }}</p>
    <p>
        <a href="{{ app(\App\Services\SearchFilterHelper::class)->searchUrlFromFilters($savedSearch->filters ?? []) }}" style="color: #1d4ed8;">
            {{ __('messages.saved_search_alert_cta') }}
        </a>
    </p>
    <p style="font-size: 12px; color: #64748b;">{{ __('messages.saved_search_alert_footer') }}</p>
</body>
</html>