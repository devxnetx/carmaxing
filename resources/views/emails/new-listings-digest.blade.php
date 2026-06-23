<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #0f172a;">
    <p>{{ __('messages.new_listings_digest_greeting', ['name' => $user->name]) }}</p>
    <p>{{ __('messages.new_listings_digest_intro', ['count' => $listings->count()]) }}</p>
    <ul>
        @foreach($listings as $listing)
            <li style="margin-bottom: 0.75rem;">
                <a href="{{ route('listings.show', $listing) }}" style="color: #1d4ed8;">
                    {{ $listing->composeDisplayTitle() }}
                </a>
                @if($listing->hasFixedPrice())
                    — {{ number_format($listing->price) }} €
                @endif
            </li>
        @endforeach
    </ul>
    <p style="font-size: 12px; color: #64748b;">
        {{ __('messages.digest_footer') }}
        <a href="{{ route('subscriptions.index') }}" style="color: #1d4ed8;">{{ __('messages.subscriptions') }}</a>
    </p>
</body>
</html>