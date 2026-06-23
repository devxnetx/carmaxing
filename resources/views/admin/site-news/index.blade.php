@extends('layouts.admin')

@section('title', __('admin.nav_email_campaigns'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_email_campaigns') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.email_campaigns_subtitle') }}</p>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.email_stats_news_subscribers') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['news_subscribers']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.email_stats_news_non_subscribers') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['news_non_subscribers']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.email_stats_price_digest') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['price_digest']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.email_stats_new_listings') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($stats['new_listings_digest']) }}</div>
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-2">
        <div class="card p-6">
            <h2 class="font-semibold">{{ __('admin.site_news_compose') }}</h2>
            <form method="POST" action="{{ route('admin.site-news.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label" for="title">{{ __('admin.site_news_title') }}</label>
                    <input type="text" name="title" id="title" class="input" value="{{ old('title') }}" required maxlength="200">
                </div>
                <div>
                    <label class="label" for="body">{{ __('admin.site_news_body') }}</label>
                    <textarea name="body" id="body" rows="8" class="input" required maxlength="10000">{{ old('body') }}</textarea>
                </div>
                <div>
                    <label class="label">{{ __('admin.site_news_recipient_target') }}</label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="recipient_target" value="subscribers" class="mt-1" @checked(old('recipient_target', 'subscribers') === 'subscribers')>
                            <span>
                                <span class="font-medium">{{ __('admin.site_news_target_subscribers') }}</span>
                                <span class="block text-xs text-[var(--color-text-muted)]">{{ __('admin.site_news_target_subscribers_hint', ['count' => number_format($stats['news_subscribers'])]) }}</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="recipient_target" value="non_subscribers" class="mt-1" @checked(old('recipient_target') === 'non_subscribers')>
                            <span>
                                <span class="font-medium">{{ __('admin.site_news_target_non_subscribers') }}</span>
                                <span class="block text-xs text-[var(--color-text-muted)]">{{ __('admin.site_news_target_non_subscribers_hint', ['count' => number_format($stats['news_non_subscribers'])]) }}</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="recipient_target" value="all" class="mt-1" @checked(old('recipient_target') === 'all')>
                            <span>
                                <span class="font-medium">{{ __('admin.site_news_target_all') }}</span>
                                <span class="block text-xs text-[var(--color-text-muted)]">{{ __('admin.site_news_target_all_hint', ['count' => number_format($stats['news_subscribers'] + $stats['news_non_subscribers'])]) }}</span>
                            </span>
                        </label>
                    </div>
                </div>
                <p class="text-xs text-[var(--color-text-muted)]">{{ __('admin.site_news_send_hint') }}</p>
                <button type="submit" class="btn-primary" onclick="return confirm('{{ __('admin.site_news_send_confirm') }}')">
                    {{ __('admin.site_news_send') }}
                </button>
            </form>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold">{{ __('admin.email_subscribers_list') }}</h2>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.email_subscribers_list_hint') }}</p>

            <x-admin-table
                class="mt-4"
                :headers="[__('pages.contact.name'), __('messages.email'), __('admin.email_col_price'), __('admin.email_col_new_cars'), __('admin.email_col_news')]"
                :column-classes="[null, null, 'w-16', 'w-16', 'w-16']"
            >
                @forelse($subscribers as $subscriber)
                    <tr class="border-t border-[var(--color-border)]">
                        <td class="px-4 py-3 font-medium">{{ $subscriber->name }}</td>
                        <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($subscriber->subscribe_price_digest)
                                <x-icon name="check" class="mx-auto h-4 w-4 text-green-600" />
                            @else
                                <span class="text-[var(--color-text-muted)]">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($subscriber->subscribe_new_listings_digest)
                                <x-icon name="check" class="mx-auto h-4 w-4 text-green-600" />
                            @else
                                <span class="text-[var(--color-text-muted)]">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <x-icon name="check" class="mx-auto h-4 w-4 text-green-600" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.email_subscribers_empty') }}</td>
                    </tr>
                @endforelse
            </x-admin-table>

            <div class="mt-4">{{ $subscribers->links() }}</div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold">{{ __('admin.site_news_history') }}</h2>
        <x-admin-table
            :headers="[__('admin.site_news_title'), __('admin.site_news_recipient_target'), __('admin.sent_at'), __('admin.site_news_recipients'), __('admin.sent_by')]"
            :column-classes="[null, 'w-40', 'w-40', 'w-28', 'w-40']"
        >
            @forelse($posts as $post)
                <tr class="border-t border-[var(--color-border)]">
                    <td class="px-4 py-3 font-medium">{{ $post->title }}</td>
                    <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ __('admin.site_news_target_'.$post->recipient_target) }}</td>
                    <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $post->sent_at?->format('d.m.Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ number_format($post->recipient_count) }}</td>
                    <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $post->sentBy?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.site_news_empty') }}</td>
                </tr>
            @endforelse
        </x-admin-table>

        <div class="mt-4">{{ $posts->links() }}</div>
    </div>
</div>
@endsection