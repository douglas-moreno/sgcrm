<x-mail::message>
# Bem-vindo ao sgCrm, {{ $user->name }}

Sua conta foi criada pelo administrador da sua equipe.

**Email:** {{ $user->email }}
**Senha temporária:** {{ $temporaryPassword }}

Você será solicitado a alterar esta senha no primeiro login.

<x-mail::button :url="$loginUrl">Entrar</x-mail::button>

Se você não esperava este email, por favor entre em contato com seu administrador.

Obrigado,<br>
A equipe do sgCrm
</x-mail::message>
