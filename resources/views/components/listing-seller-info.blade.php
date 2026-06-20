@props(['listing'])

<section>
    <h2 class="mb-4 text-lg font-semibold">{{ __('messages.seller_info') }}</h2>

    @if($listing->company)
        <x-company-profile-card
            :company="$listing->company"
            :listings-count="$listing->company->listings_count ?? null"
            :phone="$listing->contactPhone()"
            :phone-click-url="route('listings.phone-click', $listing)"
            :show-cover="false"
        />
    @else
        <div class="card overflow-hidden p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-[var(--color-surface-3)]">
                        <x-icon name="user" class="h-7 w-7 text-[var(--color-text-muted)]" />
                    </div>
                    <div>
                        <div class="text-lg font-semibold">{{ __('messages.private_seller') }}</div>
                        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.private_seller_desc') }}</p>
                    </div>
                </div>

                @if($listing->contactPhone())
                    <x-phone-reveal-button
                        :phone="$listing->contactPhone()"
                        :phone-click-url="route('listings.phone-click', $listing)"
                        class="btn-primary w-full shrink-0 sm:w-auto"
                    />
                @endif
            </div>
        </div>
    @endif
</section>