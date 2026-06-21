@extends('layouts.admin')

@section('title', __('admin.nav_logs'))

@section('content')
<div class="w-full space-y-6">
    <div>
        <h1 class="text-2xl font-bold">{{ __('admin.nav_logs') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.logs_subtitle') }}</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
        <p class="font-medium">{{ __('admin.logs_cloud_hint_title') }}</p>
        <p class="mt-1">{{ __('admin.logs_cloud_hint_body') }}</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="space-y-4">
            <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                <h2 class="text-sm font-semibold">{{ __('admin.logs_files') }}</h2>
                <p class="mt-1 break-all text-xs text-[var(--color-text-muted)]">{{ $logDirectory }}</p>

                @if($files->isEmpty())
                    <p class="mt-4 text-sm text-[var(--color-text-muted)]">{{ __('admin.logs_empty') }}</p>
                @else
                    <ul class="mt-4 space-y-1">
                        @foreach($files as $file)
                            <li>
                                <a href="{{ route('admin.logs.index', ['file' => $file['filename'], 'lines' => $lines, 'errors_only' => $errorsOnly ? 1 : 0]) }}"
                                   @class([
                                       'block rounded-lg px-3 py-2 text-sm transition',
                                       'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-200' => $selected === $file['filename'],
                                       'hover:bg-[var(--color-surface-3)]' => $selected !== $file['filename'],
                                   ])>
                                    <span class="font-medium">{{ $file['filename'] }}</span>
                                    <span class="mt-0.5 block text-xs text-[var(--color-text-muted)]">
                                        {{ \App\Support\ApplicationLogReader::formatBytes($file['size']) }}
                                        · {{ \Carbon\Carbon::createFromTimestamp($file['modified_at'])->format('d.m.Y H:i') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                <h2 class="text-sm font-semibold">{{ __('admin.logs_env_title') }}</h2>
                <dl class="mt-3 space-y-2 text-xs">
                    @foreach($environment as $item)
                        <div>
                            <dt class="font-mono text-[var(--color-text-muted)]">{{ $item['key'] }}</dt>
                            <dd class="font-mono">{{ $item['value'] }}</dd>
                            @if($item['note'])
                                <dd class="mt-0.5 text-amber-700 dark:text-amber-300">{{ $item['note'] }}</dd>
                            @endif
                        </div>
                    @endforeach
                </dl>
            </div>
        </aside>

        <section class="min-w-0 space-y-4">
            @if($files->isNotEmpty())
                <form method="GET" action="{{ route('admin.logs.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                    <input type="hidden" name="file" value="{{ $selected }}">

                    <div>
                        <label class="label">{{ __('admin.logs_lines') }}</label>
                        <select name="lines" class="input">
                            @foreach([200, 500, 1000, 2000] as $option)
                                <option value="{{ $option }}" @selected($lines === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="errors_only" value="1" class="rounded border-[var(--color-border)]" @checked($errorsOnly)>
                        {{ __('admin.logs_errors_only') }}
                    </label>

                    <button type="submit" class="btn btn-primary">{{ __('admin.logs_refresh') }}</button>
                </form>
            @endif

            @if($error)
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $error }}
                </div>
            @elseif($content !== null)
                <div class="overflow-hidden rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)]">
                    <div class="border-b border-[var(--color-border)] px-4 py-3 text-sm font-medium">
                        {{ $selected }}
                    </div>
                    <pre class="max-h-[70vh] overflow-auto bg-zinc-950 p-4 text-xs leading-relaxed text-zinc-100 whitespace-pre-wrap break-words">{{ $content }}</pre>
                </div>
            @endif

            <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4 text-sm text-[var(--color-text-muted)]">
                <h2 class="font-semibold text-[var(--color-text)]">{{ __('admin.logs_pulse_help_title') }}</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>{{ __('admin.logs_pulse_help_1') }}</li>
                    <li>{{ __('admin.logs_pulse_help_2') }}</li>
                    <li>{{ __('admin.logs_pulse_help_3') }}</li>
                </ul>
            </div>
        </section>
    </div>
</div>
@endsection