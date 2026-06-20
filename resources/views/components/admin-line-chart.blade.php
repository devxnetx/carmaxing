@props([
    'title',
    'data' => [],
    'days' => 30,
])

@php
    $points = collect($data);
    $max = max(1, (int) $points->max('count'));
    $total = (int) $points->sum('count');
    $width = 100;
    $height = 40;
    $step = $points->count() > 1 ? $width / ($points->count() - 1) : $width;
    $coords = $points->values()->map(function ($row, $index) use ($max, $height, $step) {
        $x = round($index * $step, 2);
        $y = round($height - (($row['count'] / $max) * ($height - 4)) - 2, 2);

        return "{$x},{$y}";
    })->join(' ');
    $area = $coords.' '.$width.','.$height.' 0,'.$height;
    $labelEvery = max(1, (int) floor($points->count() / 6));
@endphp

<div {{ $attributes->class(['card p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">{{ $title }}</h2>
            <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('admin.chart_last_days', ['days' => $days]) }}</p>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-brand-600">{{ number_format($total) }}</div>
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('admin.chart_total_period') }}</div>
        </div>
    </div>

    <div class="mt-4">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-36 w-full" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="chart-fill-{{ md5($title) }}" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="rgb(37 99 235 / 0.35)" />
                    <stop offset="100%" stop-color="rgb(37 99 235 / 0.02)" />
                </linearGradient>
            </defs>
            @if($coords !== '')
                <polygon points="{{ $area }}" fill="url(#chart-fill-{{ md5($title) }})" />
                <polyline
                    points="{{ $coords }}"
                    fill="none"
                    stroke="rgb(37 99 235)"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                />
            @endif
        </svg>

        <div class="mt-2 flex justify-between text-[10px] text-[var(--color-text-muted)]">
            @foreach($points as $index => $row)
                @if($index % $labelEvery === 0 || $index === $points->count() - 1)
                    <span>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d.m') }}</span>
                @endif
            @endforeach
        </div>
    </div>
</div>