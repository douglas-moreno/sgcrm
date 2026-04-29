<x-mail::message>
# Password changed

Hi {{ $user->name }},

The password for your sgCrm account ({{ $user->email }}) was just updated. If this was you, no further action is needed.

If you did **not** make this change, please reset your password again immediately and contact support.

Thanks,<br>
The sgCrm team
</x-mail::message>
