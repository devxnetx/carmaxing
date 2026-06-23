<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #0f172a;">
    <p>{{ __('messages.site_news_greeting', ['name' => $user->name]) }}</p>
    <h2 style="font-size: 1.125rem; margin: 1rem 0 0.5rem;">{{ $post->title }}</h2>
    <div style="white-space: pre-wrap;">{{ $post->body }}</div>
    <p style="font-size: 12px; color: #64748b; margin-top: 1.5rem;">
        {{ __('messages.digest_footer') }}
        <a href="{{ route('subscriptions.index') }}" style="color: #1d4ed8;">{{ __('messages.subscriptions') }}</a>
    </p>
</body>
</html>