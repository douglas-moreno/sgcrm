<x-mail::message>
# You're invited, {{ $invite->name }}

{{ $invite->invitedBy?->name ?? 'A teammate' }} has invited you to join **{{ $invite->company?->name }}** on sgCrm.

Click below to set your password and access your workspace. This invitation expires on **{{ $invite->expires_at->format('M j, Y H:i') }}**.

<x-mail::button :url="$url">Accept invitation</x-mail::button>

If you did not expect this invitation you can ignore this message.

Thanks,<br>
The sgCrm team
</x-mail::message>
