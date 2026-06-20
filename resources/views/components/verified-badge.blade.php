@props(['company'])

@if($company->isVerifiedDealer())
    <span
        class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
        title="{{ __('messages.verified_dealer_tooltip') }}"
    >
        <x-icon name="check" class="h-3.5 w-3.5" />
        {{ __('messages.verified_dealer') }}
    </span>
@endif