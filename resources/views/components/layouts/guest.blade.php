@props([
    'title' => null,
    'heading' => null,
    'subheading' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <wireui:scripts />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="min-h-screen bg-surface-alt text-ink antialiased" data-layout="guest">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <header class="lg:hidden flex items-center justify-between px-6 py-4 border-b border-outline">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-sm font-semibold text-primary-500" data-testid="brand">
                <span class="inline-block h-3 w-3 rounded-sm bg-primary-500"></span>
                {{ config('app.name') }}
            </a>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 py-10 lg:px-16" data-testid="guest-main">
            <div class="w-full max-w-md">
                <a href="{{ url('/') }}" class="hidden lg:flex items-center gap-2 text-sm font-semibold text-primary-500 mb-10" data-testid="brand">
                    <span class="inline-block h-3 w-3 rounded-sm bg-primary-500"></span>
                    {{ config('app.name') }}
                </a>

                @if ($heading)
                    <h1 class="text-2xl font-bold text-ink leading-tight">{{ $heading }}</h1>
                @endif
                @if ($subheading)
                    <p class="text-sm text-ink-muted mt-2">{{ $subheading }}</p>
                @endif

                <div class="mt-8 space-y-5">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <aside class="hidden lg:flex relative w-1/2 max-w-160 bg-primary-500 text-white items-center justify-center overflow-hidden" data-testid="guest-aside" aria-hidden="true">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.4) 0, transparent 40%), radial-gradient(circle at 80% 80%, rgba(255,255,255,0.3) 0, transparent 45%);"></div>
            <div class="relative z-10 px-12 max-w-md text-center">
                <p class="text-xs uppercase tracking-[0.4em] text-white/70">sgCrm</p>
                <h2 class="text-3xl font-semibold leading-tight mt-4">Pipeline de vendas + WhatsApp, em um só lugar.</h2>
                <p class="text-sm text-white/80 mt-4">Gerencie leads em um quadro Kanban e converse com clientes sem sair do CRM.</p>
            </div>
        </aside>
    </div>

    @livewireScripts
</body>
</html>
