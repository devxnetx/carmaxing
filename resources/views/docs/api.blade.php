@extends('layouts.app')

@section('title', __('messages.api_docs'))

@section('content')
<div
    class="mx-auto max-w-4xl px-4 py-8"
    @if($canTryApi)
        x-data="apiDocsTester(@js([
            'endpoints' => $endpoints,
            'tryUrl' => route('docs.api.try'),
            'sampleListingUrl' => route('docs.api.sample-listing'),
            'csrf' => csrf_token(),
            'sessionApiKey' => $sessionApiKey,
            'messages' => [
                'keyRequired' => __('api_docs.playground_api_key_required'),
                'error' => __('api_docs.playground_error'),
                'noListing' => __('api_docs.playground_no_listing'),
                'waiting' => __('api_docs.playground_waiting'),
            ],
        ]))"
        x-init="init()"
    @endif
>
    <h1 class="text-3xl font-bold">{{ config('app.name', 'CARMAXING') }} API v1</h1>
    <p class="mt-2 text-[var(--color-text-muted)]">{{ __('api_docs.intro') }}</p>
    <p class="mt-2 text-sm"><strong>{{ __('api_docs.base_url') }}:</strong> <code class="rounded bg-[var(--color-surface-3)] px-2 py-0.5">{{ $baseUrl }}</code></p>

    @guest
        <p class="mt-4 text-sm font-medium text-red-600 dark:text-red-400">
            {{ __('api_docs.playground_login_required') }}
            <a href="{{ route('login') }}" class="underline hover:text-red-700 dark:hover:text-red-300">{{ __('messages.login') }}</a>
        </p>
    @else
        @if(! $isCompanyUser)
            <p class="mt-4 text-sm text-[var(--color-text-muted)]">{{ __('api_docs.playground_company_only') }}</p>
        @elseif(! $hasActiveApiKey)
            <p class="mt-4 text-sm font-medium text-red-600 dark:text-red-400">
                {{ __('api_docs.playground_generate_key') }}
            </p>
            <a href="{{ route('settings') }}" class="btn-primary mt-3 inline-flex text-sm">{{ __('messages.api_keys') }} →</a>
        @else
            <p class="mt-4 text-sm text-green-700 dark:text-green-300">{{ __('api_docs.playground_enabled') }}</p>
            <a href="{{ route('settings') }}" class="btn-secondary mt-3 inline-flex text-sm">{{ __('messages.api_keys') }} →</a>
        @endif
    @endguest

    <section class="card mt-8 space-y-4 p-6">
        <h2 class="text-lg font-semibold">{{ __('api_docs.auth_title') }}</h2>
        <p class="text-sm text-[var(--color-text-muted)]">{{ __('api_docs.auth_text') }}</p>
        <p class="text-sm text-[var(--color-text-muted)]">{{ __('api_docs.auth_header') }}</p>
        <pre class="overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">Authorization: Bearer ac_xxxxxxxxxxxxxxxxxxxxxxxx
# or
X-API-Key: ac_xxxxxxxxxxxxxxxxxxxxxxxx</pre>
    </section>

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold mb-4">{{ __('api_docs.limits_title') }}</h2>
        <p class="mb-4 text-sm text-[var(--color-text-muted)]">{{ __('api_docs.limits_intro') }}</p>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-[var(--color-border)]">
                <tr><td class="py-2 text-[var(--color-text-muted)]">{{ __('api_docs.limit_rpm') }}</td><td class="py-2 font-medium">{{ $requestsPerMinute }}</td></tr>
                <tr><td class="py-2 text-[var(--color-text-muted)]">{{ __('api_docs.limit_daily') }}</td><td class="py-2 font-medium">{{ $listingsPerDay }}</td></tr>
                <tr><td class="py-2 text-[var(--color-text-muted)]">{{ __('api_docs.limit_page') }}</td><td class="py-2 font-medium">{{ $maxPerPage }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold">{{ __('api_docs.php_client_title') }}</h2>
        <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('api_docs.php_client_help') }}</p>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">{{ \App\Support\ApiDocsPhpExample::build('GET', $baseUrl.'/catalog') }}</pre>
    </section>

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold mb-2">{{ __('api_docs.identifiers_title') }}</h2>
        <p class="text-sm text-[var(--color-text-muted)]">{{ __('api_docs.identifiers_text') }}</p>
        <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('api_docs.fields_note') }}</p>
    </section>

    @foreach($endpoints as $endpoint)
        <section class="card mt-6 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">{{ $endpoint['method'] }}</span>
                <code class="text-sm font-semibold text-brand-600">{{ $endpoint['path'] }}</code>
                @if($canTryApi && ! empty($endpoint['runnable']))
                    <button
                        type="button"
                        class="btn-primary ml-auto text-xs"
                        @click="openDrawer(@js($endpoint))"
                    >
                        {{ __('api_docs.playground_run_test') }}
                    </button>
                @endif
            </div>
            <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ $endpoint['desc'] }}</p>

            @if(! empty($endpoint['playground_disabled']))
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ __('api_docs.playground_delete_disabled') }}</p>
            @endif

            @if($endpoint['request'])
                <h4 class="mt-4 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">Request body</h4>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">{{ $endpoint['request'] }}</pre>
            @endif

            <h4 class="mt-4 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('api_docs.playground_example_response') }}</h4>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">{{ $endpoint['response'] }}</pre>

            @if(! empty($endpoint['php']))
                <h4 class="mt-4 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('api_docs.playground_php_request') }}</h4>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">{{ $endpoint['php'] }}</pre>
            @endif
        </section>
    @endforeach

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold">{{ __('api_docs.errors_title') }}</h2>
        <div class="mt-4 space-y-3 text-sm">
            <div><code class="text-brand-600">401</code> — Invalid or missing API key</div>
            <div><code class="text-brand-600">404</code> — Listing not found (wrong id / ad_number / external_id)</div>
            <div><code class="text-brand-600">409</code> — external_id already exists on POST</div>
            <div><code class="text-brand-600">422</code> — Validation error</div>
            <div><code class="text-brand-600">429</code> — Rate limit or daily listing cap exceeded</div>
        </div>
        <pre class="mt-4 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-sm">{
  "message": "Daily listing creation limit reached.",
  "limit": {{ $listingsPerDay }}
}</pre>
    </section>

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold">OpenAPI</h2>
        <a href="{{ url('/docs/openapi.yaml') }}" class="btn-secondary mt-3 inline-flex">openapi.yaml</a>
    </section>

    @if($canTryApi)
        <div
            x-show="drawerOpen"
            x-cloak
            class="fixed inset-0 z-40 bg-black/40"
            @click="closeDrawer()"
        ></div>

        <aside
            x-show="drawerOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col border-l border-[var(--color-border)] bg-[var(--color-surface)] shadow-2xl"
            @keydown.escape.window="closeDrawer()"
        >
            <div class="flex items-center justify-between border-b border-[var(--color-border)] px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('api_docs.playground_run_test') }}</p>
                    <p class="mt-1 font-semibold" x-text="active ? `${active.method} ${active.path}` : ''"></p>
                </div>
                <button type="button" class="btn-secondary px-3 py-1.5 text-xs" @click="closeDrawer()">×</button>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-5">
                <div>
                    <label class="label" for="docs-api-key">{{ __('api_docs.playground_api_key') }}</label>
                    <input
                        id="docs-api-key"
                        type="password"
                        class="input"
                        x-model="apiKey"
                        @change="persistApiKey()"
                        placeholder="ac_..."
                        autocomplete="off"
                    >
                    <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('api_docs.playground_api_key_help') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">{{ __('api_docs.playground_php_request') }}</h3>
                    <pre class="mt-2 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-xs leading-relaxed" x-text="phpExample"></pre>
                </div>

                <div>
                    <button
                        type="button"
                        class="btn-primary w-full text-sm"
                        @click="runTest()"
                        :disabled="loading"
                    >
                        <span x-show="!loading">{{ __('api_docs.playground_run_in_drawer') }}</span>
                        <span x-show="loading" x-cloak>{{ __('api_docs.playground_running') }}</span>
                    </button>
                    <p x-show="error" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="error"></p>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold">{{ __('api_docs.playground_response') }}</h3>
                        <span
                            x-show="responseStatus"
                            x-cloak
                            class="rounded bg-[var(--color-surface-3)] px-2 py-0.5 text-xs font-medium"
                            x-text="`${@js(__('api_docs.playground_status'))}: ${responseStatus}`"
                        ></span>
                    </div>
                    <pre
                        class="mt-2 min-h-32 overflow-x-auto rounded-lg bg-[var(--color-surface-3)] p-4 text-xs leading-relaxed"
                        x-text="responseOutput || @js(__('api_docs.playground_waiting'))"
                    ></pre>
                </div>
            </div>
        </aside>
    @endif
</div>

@if($canTryApi)
    @push('scripts')
        <script>
            function apiDocsTester(config) {
                return {
                    endpoints: config.endpoints,
                    tryUrl: config.tryUrl,
                    sampleListingUrl: config.sampleListingUrl,
                    csrf: config.csrf,
                    messages: config.messages,
                    sessionApiKey: config.sessionApiKey || '',
                    apiKey: '',
                    listingId: null,
                    drawerOpen: false,
                    active: null,
                    phpExample: '',
                    responseOutput: '',
                    responseStatus: null,
                    loading: false,
                    error: null,

                    init() {
                        const stored = sessionStorage.getItem('carmaxing_api_key') || '';
                        this.apiKey = this.sessionApiKey || stored;
                        this.fetchSampleListingId();
                    },

                    async fetchSampleListingId() {
                        try {
                            const response = await fetch(this.sampleListingUrl, {
                                headers: { Accept: 'application/json' },
                            });
                            if (response.ok) {
                                const data = await response.json();
                                this.listingId = data.listing_id ?? null;
                            }
                        } catch (e) {}
                    },

                    persistApiKey() {
                        if (this.apiKey) {
                            sessionStorage.setItem('carmaxing_api_key', this.apiKey);
                        }
                    },

                    openDrawer(endpoint) {
                        this.active = endpoint;
                        this.drawerOpen = true;
                        this.responseOutput = '';
                        this.responseStatus = null;
                        this.error = null;
                        this.phpExample = this.buildPhpExample(endpoint);
                        document.body.classList.add('overflow-hidden');
                    },

                    closeDrawer() {
                        this.drawerOpen = false;
                        document.body.classList.remove('overflow-hidden');
                    },

                    buildPhpExample(endpoint) {
                        let php = endpoint.php || '';
                        const key = this.apiKey || 'ac_YOUR_API_KEY';
                        php = php.replaceAll('ac_YOUR_API_KEY', key);

                        if (endpoint.needs_listing && this.listingId) {
                            php = php.replace(/\/listings\/[^'"\s]+/g, `/listings/${this.listingId}`);
                        }

                        return php;
                    },

                    async runTest() {
                        if (!this.active) {
                            return;
                        }

                        if (!this.apiKey) {
                            this.error = this.messages.keyRequired;
                            return;
                        }

                        this.persistApiKey();
                        this.loading = true;
                        this.error = null;

                        const payload = {
                            api_key: this.apiKey,
                            method: this.active.method,
                            path: this.active.api_path,
                            body: this.active.try_body ?? null,
                            query: this.active.try_query ?? null,
                        };

                        if (this.active.needs_listing) {
                            if (!this.listingId) {
                                this.error = this.messages.noListing;
                                this.loading = false;
                                return;
                            }
                            payload.listing_id = String(this.listingId);
                        }

                        try {
                            const response = await fetch(this.tryUrl, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                },
                                body: JSON.stringify(payload),
                            });

                            const data = await response.json();
                            this.responseStatus = data.status ?? response.status;
                            this.responseOutput = JSON.stringify(data.body ?? data, null, 2);

                            if (data.php_example) {
                                this.phpExample = data.php_example;
                            }

                            if (!response.ok) {
                                this.error = data.body?.message || data.message || this.messages.error;
                            }

                            if (this.active.id === 'listings_create' && data.body?.data?.ad_number) {
                                this.listingId = data.body.data.ad_number;
                            } else if (this.active.id === 'listings_create' && data.body?.data?.id) {
                                this.listingId = data.body.data.id;
                            }
                        } catch (e) {
                            this.error = this.messages.error;
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            }
        </script>
    @endpush
@endif
@endsection