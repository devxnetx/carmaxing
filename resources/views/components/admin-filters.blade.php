@props(['action'])

<form method="GET" action="{{ $action }}" class="card mb-6 flex w-full flex-wrap items-end gap-3 p-4">
    {{ $slot }}
    <button type="submit" class="btn-primary text-sm">{{ __('admin.filter') }}</button>
    <a href="{{ $action }}" class="btn-secondary text-sm">{{ __('admin.clear') }}</a>
</form>