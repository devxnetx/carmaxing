{{ __('pages.contact.email_intro') }}

{{ __('pages.contact.name') }}: {{ $contactMessage->name }}
{{ __('messages.email') }}: {{ $contactMessage->email }}
@if($contactMessage->subject)
{{ __('pages.contact.subject') }}: {{ $contactMessage->subject }}
@endif

{{ __('pages.contact.message') }}
----------------------------------------
{{ $contactMessage->message }}
----------------------------------------

{{ __('pages.contact.email_meta', [
    'date' => $contactMessage->created_at?->format('d.m.Y H:i') ?? '',
    'locale' => strtoupper((string) $contactMessage->locale),
]) }}