{{ __('messages.welcome_email_greeting', ['name' => $user->name]) }}

{{ __('messages.welcome_email_intro') }}

{{ __('messages.welcome_email_body') }}

{{ __('messages.welcome_email_cta') }}: {{ route('search') }}