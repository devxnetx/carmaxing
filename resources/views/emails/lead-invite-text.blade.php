{{ __('admin.lead_invite_email_greeting', ['name' => $lead->name]) }}

{{ __('admin.lead_invite_email_intro', ['app' => config('app.name')]) }}

{{ __('admin.lead_invite_email_body') }}

{{ __('admin.lead_invite_email_cta') }}: {{ route('login') }}