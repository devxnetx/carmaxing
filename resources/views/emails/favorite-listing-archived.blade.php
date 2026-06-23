<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #0f172a;">
    <p>{{ __('messages.favorite_archived_greeting', ['name' => $user->name]) }}</p>
    <p>{{ __('messages.favorite_archived_body', ['vehicle' => $listing->composeDisplayTitle()]) }}</p>
    <p>
        <a href="{{ route('listings.show', $listing) }}" style="color: #1d4ed8;">
            {{ __('messages.favorite_archived_cta') }}
        </a>
    </p>
    <p style="font-size: 12px; color: #64748b;">{{ __('messages.favorite_notification_footer') }}</p>
</body>
</html>