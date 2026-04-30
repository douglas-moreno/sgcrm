<x-mail::message>
# Reset your sgCrm password

Hi {{ $user->name }},

We received a request to reset the password for the sgCrm account at **{{ $user->email }}**.

<x-mail::button :url="$url">Reset password</x-mail::button>

This link expires in 60 minutes. If you did not request a password reset you can safely ignore this email.

Thanks,<br>
The sgCrm team
</x-mail::message>
