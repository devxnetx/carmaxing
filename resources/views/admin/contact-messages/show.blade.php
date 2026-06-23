@extends('layouts.admin')

@section('title', __('admin.contact_message_details'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.contact-messages.index') }}" class="text-sm text-brand-600 hover:underline">← {{ __('admin.nav_contact_messages') }}</a>
            <h1 class="mt-2 text-2xl font-bold">{{ __('admin.contact_message_details') }}</h1>
        </div>
        @if($message->isUnread())
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">{{ __('admin.unread') }}</span>
        @endif
    </div>

    <div class="card space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('pages.contact.name') }}</div>
                <div class="mt-1 font-medium">{{ $message->name }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('messages.email') }}</div>
                <a href="mailto:{{ $message->email }}" class="mt-1 block font-medium text-brand-600 hover:underline">{{ $message->email }}</a>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('pages.contact.subject') }}</div>
                <div class="mt-1">{{ $message->subject ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('admin.received_at') }}</div>
                <div class="mt-1">{{ $message->created_at?->format('d.m.Y H:i') }}</div>
            </div>
        </div>

        <div>
            <div class="text-xs uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('pages.contact.message') }}</div>
            <p class="mt-2 whitespace-pre-line leading-relaxed text-[var(--color-text-muted)]">{{ $message->message }}</p>
        </div>

        @if($message->ip_address || $message->locale)
            <div class="border-t border-[var(--color-border)] pt-4 text-xs text-[var(--color-text-muted)]">
                @if($message->locale)
                    <div>{{ __('admin.locale') }}: {{ strtoupper($message->locale) }}</div>
                @endif
                @if($message->ip_address)
                    <div>{{ __('admin.ip_address') }}: {{ $message->ip_address }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection