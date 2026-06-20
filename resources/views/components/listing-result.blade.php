{{-- Optional list view: cards on mobile, rows on desktop (search & dealer pages). --}}
@props(['listing', 'showFavorite' => true, 'favorited' => false])

<div class="sm:hidden">
    <x-listing-grid-card :listing="$listing" :favorited="$favorited" :show-favorite="$showFavorite" />
</div>
<div class="hidden sm:block">
    <x-listing-row :listing="$listing" :favorited="$favorited" :show-favorite="$showFavorite" />
</div>