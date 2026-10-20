<x-mail::message>
# {{ __('staff.mail.heading') }}

{{ __('staff.mail.body', ['inviter' => $inviter, 'role' => $role]) }}

<x-mail::button :url="$url">
{{ __('staff.mail.action') }}
</x-mail::button>

{{ __('staff.mail.two_factor') }}

{{ __('team.mail.expires', ['date' => $expires]) }}
</x-mail::message>
