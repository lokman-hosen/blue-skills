<x-mail::message>
# {{ __('team.mail.heading', ['organization' => $organization]) }}

{{ __('team.mail.body', ['inviter' => $inviter ?? 'BLUESKILLS', 'organization' => $organization, 'role' => $role]) }}

<x-mail::button :url="$url">
{{ __('team.mail.action') }}
</x-mail::button>

{{ __('team.mail.expires', ['date' => $expires]) }}

{{ __('team.mail.ignore') }}
</x-mail::message>
