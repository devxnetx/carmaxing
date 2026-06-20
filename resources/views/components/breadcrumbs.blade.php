@props(['items'])

<nav aria-label="Breadcrumb" {{ $attributes->class(['text-sm text-[var(--color-text-muted)]']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                @if(! $loop->first)
                    <span class="text-[var(--color-border)]" aria-hidden="true">/</span>
                @endif
                @if(($item['url'] ?? null) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="transition hover:text-brand-600">{{ $item['name'] }}</a>
                @else
                    <span @if($loop->last) aria-current="page" class="text-[var(--color-text)]" @endif>{{ $item['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>