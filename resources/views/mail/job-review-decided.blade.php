<x-mail::message>
# {{ __("jobs.review_mail.heading.{$decision}", ['title' => $title]) }}

{{ __("jobs.review_mail.body.{$decision}", ['title' => $title, 'code' => $code]) }}

@if ($reason)
**{{ __('organization.review_mail.reason') }}:** {{ $reason }}
@endif

@if ($note)
> {{ $note }}
@endif

<x-mail::button :url="$url">
{{ __('jobs.review_mail.action') }}
</x-mail::button>
</x-mail::message>
