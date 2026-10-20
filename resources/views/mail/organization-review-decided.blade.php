<x-mail::message>
# {{ __("organization.review_mail.heading.{$decision}", ['organization' => $organization]) }}

{{ __("organization.review_mail.body.{$decision}", ['organization' => $organization]) }}

@if ($reason)
**{{ __('organization.review_mail.reason') }}:** {{ $reason }}
@endif

@if ($note)
> {{ $note }}
@endif

<x-mail::button :url="$url">
{{ __("organization.review_mail.action.{$decision}") }}
</x-mail::button>
</x-mail::message>
