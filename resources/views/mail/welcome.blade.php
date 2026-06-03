<x-mail::message>
# Bem-vindo ao sgCrm, {{ $user->name }}!

Seu espaço de trabalho está pronto. Agora você pode começar a adicionar leads, construir seu funil de vendas e conversar com clientes via WhatsApp — tudo em um único lugar.

<x-mail::button :url="config('app.url')">Abrir sgCrm</x-mail::button>

Se você não criou esta conta, por favor ignore esta mensagem.

Obrigado,<br>
A equipe do sgCrm
</x-mail::message>
