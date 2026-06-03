<x-mail::message>
# Resetar sua senha do sgCrm

Oi {{ $user->name }},

Recebemos uma solicitação para redefinir a senha da conta no sgCrm em **{{ $user->email }}**.

<x-mail::button :url="$url">Resetar senha</x-mail::button>

Este link expira em 60 minutos. Se você não solicitou a redefinição de senha, pode ignorar este email.

Obrigado,<br>
A equipe do sgCrm
</x-mail::message>
