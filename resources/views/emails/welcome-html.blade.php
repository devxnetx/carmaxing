@extends('emails.layout')

@section('body')
    <p style="margin:0 0 16px;font-size:18px;font-weight:600;color:#18181b;">
        {{ __('messages.welcome_email_greeting', ['name' => $user->name]) }}
    </p>
    <p style="margin:0 0 16px;">{{ __('messages.welcome_email_intro') }}</p>
    <p style="margin:0 0 24px;">{{ __('messages.welcome_email_body') }}</p>
    <p style="margin:0;">
        <a href="{{ route('search') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">
            {{ __('messages.welcome_email_cta') }}
        </a>
    </p>
@endsection