@extends('layouts.admin')

@section('title', __('admin.nav_contact_messages'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_contact_messages') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.contact_messages_subtitle') }}</p>
    </div>

    <x-admin-filters :action="route('admin.contact-messages.index')">
        <div>
            <label class="label">{{ __('admin.status') }}</label>
            <select name="status" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="unread" @selected(request('status') === 'unread')>{{ __('admin.unread') }}</option>
                <option value="read" @selected(request('status') === 'read')>{{ __('admin.read') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table
        :headers="[__('pages.contact.name'), __('messages.email'), __('pages.contact.subject'), __('admin.received_at'), __('admin.status')]"
        :column-classes="[null, null, null, 'w-40', 'w-28']"
    >
        @forelse($messages as $message)
            <tr class="border-t border-[var(--color-border)]">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.contact-messages.show', $message) }}" class="font-medium hover:text-brand-600">
                        {{ $message->name }}
                    </a>
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $message->email }}</td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $message->subject ?: '—' }}</td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $message->created_at?->format('d.m.Y H:i') }}</td>
                <td class="px-4 py-3">
                    @if($message->isUnread())
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ __('admin.unread') }}</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('admin.read') }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.contact_messages_empty') }}</td>
            </tr>
        @endforelse
    </x-admin-table>

    <div class="mt-6">{{ $messages->links() }}</div>
</div>
@endsection