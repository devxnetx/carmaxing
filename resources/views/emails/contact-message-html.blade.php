<p>{{ __('pages.contact.email_intro') }}</p>

<p>
    <strong>{{ __('pages.contact.name') }}:</strong> {{ $contactMessage->name }}<br>
    <strong>{{ __('messages.email') }}:</strong> <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
    @if($contactMessage->subject)
        <br><strong>{{ __('pages.contact.subject') }}:</strong> {{ $contactMessage->subject }}
    @endif
</p>

<p><strong>{{ __('pages.contact.message') }}</strong></p>
<p style="white-space: pre-line; border-left: 3px solid #e2e8f0; padding-left: 12px; color: #334155;">{{ $contactMessage->message }}</p>

<p style="font-size: 12px; color: #64748b;">
    {{ __('pages.contact.email_meta', [
        'date' => $contactMessage->created_at?->format('d.m.Y H:i') ?? '',
        'locale' => strtoupper((string) $contactMessage->locale),
    ]) }}
</p>