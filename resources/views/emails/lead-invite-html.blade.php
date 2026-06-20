@extends('emails.layout')

@section('body')
    <p style="margin:0 0 16px;font-size:18px;font-weight:600;color:#18181b;">
        {{ __('admin.lead_invite_email_greeting', ['name' => $lead->name]) }}
    </p>
    <p style="margin:0 0 16px;">{{ __('admin.lead_invite_email_intro', ['app' => config('app.name')]) }}</p>
    <p style="margin:0 0 24px;">{{ __('admin.lead_invite_email_body') }}</p>
    <p style="margin:0;">
        <a href="{{ route('login') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">
            {{ __('admin.lead_invite_email_cta') }}
        </a>
    </p>
@endsection