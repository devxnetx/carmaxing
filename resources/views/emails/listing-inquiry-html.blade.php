@extends('emails.layout')

@section('body')
    <p style="margin:0 0 16px;font-size:18px;font-weight:600;color:#18181b;">
        {{ __('messages.inquiry_email_intro', ['title' => $listing->composeDisplayTitle()]) }}
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">
        <tr>
            <td style="padding:6px 0;color:#71717a;">{{ __('messages.inquiry_email_from') }}</td>
            <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $buyer->name }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#71717a;">Email</td>
            <td style="padding:6px 0;text-align:right;">
                <a href="mailto:{{ $buyer->email }}" style="color:#2563eb;">{{ $buyer->email }}</a>
            </td>
        </tr>
        @if($listing->displayAdNumber())
            <tr>
                <td style="padding:6px 0;color:#71717a;">{{ __('messages.ad_number') }}</td>
                <td style="padding:6px 0;text-align:right;font-weight:600;">#{{ $listing->displayAdNumber() }}</td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.04em;">
        {{ __('messages.inquiry_email_message_label') }}
    </p>
    <div style="margin:0 0 24px;padding:16px;background:#f4f4f5;border-radius:8px;border:1px solid #e4e4e7;white-space:pre-wrap;font-size:15px;line-height:1.6;color:#18181b;">{{ $inquiryMessage }}</div>

    <p style="margin:0;">
        <a href="{{ route('listings.show', $listing) }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;">
            {{ __('messages.inquiry_email_view_listing') }}
        </a>
    </p>
@endsection