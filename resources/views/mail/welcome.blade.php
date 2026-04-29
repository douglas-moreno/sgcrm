<x-mail::message>
# Welcome to sgCrm, {{ $user->name }}!

Your workspace is ready. You can now start adding leads, building your sales pipeline, and chatting with customers via WhatsApp — all in one place.

<x-mail::button :url="config('app.url')">Open sgCrm</x-mail::button>

If you didn't create this account, please ignore this message.

Thanks,<br>
The sgCrm team
</x-mail::message>
