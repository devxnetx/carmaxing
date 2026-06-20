@props([
    'headers' => [],
    'columnClasses' => [],
])

<div {{ $attributes->class(['card w-full overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[var(--color-border)] bg-[var(--color-surface-3)] text-xs uppercase tracking-wide text-[var(--color-text-muted)]">
                <tr>
                    @foreach($headers as $index => $header)
                        <th @class(array_filter([
                            'px-4 py-3 font-medium',
                            'w-24' => $header === '',
                            $columnClasses[$index] ?? null,
                        ]))>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--color-border)]">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>