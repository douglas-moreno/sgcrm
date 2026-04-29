<x-mail::message>
# Welcome to sgCrm, {{ $user->name }}

Your account has been created by your team's administrator.

**Email:** {{ $user->email }}
**Temporary password:** {{ $temporaryPassword }}

You will be required to change this password on first login.

<x-mail::button :url="$loginUrl">Sign in</x-mail::button>

If you did not expect this email please contact your administrator.

Thanks,<br>
The sgCrm team
</x-mail::message>
