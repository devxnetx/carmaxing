<div
    x-data="compareTray('{{ route('compare.index') }}', '{{ route('compare.state') }}')"
    x-init="init()"
    x-show="count > 0"
    x-cloak
    class="no-print fixed bottom-[4.5rem] left-4 right-4 z-40 mx-auto max-w-lg lg:bottom-6 lg:left-auto lg:right-6"
>
    <div class="flex items-center justify-between gap-3 rounded-xl border border-brand-500/30 bg-[var(--color-surface)] px-4 py-3 shadow-lg">
        <span class="text-sm font-medium" x-text="label"></span>
        <div class="flex gap-2">
            <a :href="compareUrl" class="btn-primary px-3 py-1.5 text-xs">{{ __('messages.compare_view') }}</a>
            <button type="button" @click="clear()" class="btn-secondary px-3 py-1.5 text-xs">{{ __('messages.compare_clear') }}</button>
        </div>
    </div>
</div>