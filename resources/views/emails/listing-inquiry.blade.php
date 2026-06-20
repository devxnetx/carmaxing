{{ __('messages.inquiry_email_intro', ['title' => $listing->composeDisplayTitle()]) }}

{{ __('messages.inquiry_email_from') }}: {{ $buyer->name }} <{{ $buyer->email }}>

{{ __('messages.inquiry_email_message_label') }}
----------------------------------------
{{ $inquiryMessage }}
----------------------------------------

{{ __('messages.inquiry_email_listing') }}: {{ route('listings.show', $listing) }}
@if($listing->displayAdNumber())
{{ __('messages.ad_number') }}: #{{ $listing->displayAdNumber() }}
@endif