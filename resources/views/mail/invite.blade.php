<x-mail::message>
# Você está convidado, {{ $invite->name }}

{{ $invite->invitedBy?->name ?? 'Um colega de equipe' }} te convidou para se juntar **{{ $invite->company?->name }}** no sgCrm.

Clique abaixo para definir sua senha e acessar seu espaço de trabalho. Esta convite expira em **{{ $invite->expires_at->format('M j, Y H:i') }}**.

<x-mail::button :url="$url">Aceitar convite</x-mail::button>

Se você não esperava este convite, pode ignorar esta mensagem.

Obrigado,<br>
A equipe do sgCrm
</x-mail::message>
