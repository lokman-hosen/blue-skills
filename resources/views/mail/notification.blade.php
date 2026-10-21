<x-mail::message>
# {{ $title }}

{{ $body }}

<x-mail::button :url="$url">
{{ __('notification.mail.action') }}
</x-mail::button>

<small>{{ __('notification.mail.preferences') }}</small>
</x-mail::message>
