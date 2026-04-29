@props([
    'title' => null,
    'pageTitle' => null,
    'navRole' => null,
])

@php
    $resolvedRole = $navRole ?? auth()->user()?->roleSlug();
    $navItems = \App\Support\Navigation::items($resolvedRole);
    $currentRoute = request()->route()?->getName();
    $user = auth()->user();
@endphp

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
<body class="min-h-screen bg-surface-alt text-ink antialiased" data-layout="app" data-role="{{ $resolvedRole ?? 'guest' }}" x-data="{ mobileNav: false }">

    <aside class="hidden lg:flex fixed inset-y-0 left-0 w-20 bg-surface border-r border-outline flex-col items-center py-6 gap-2 z-30" data-testid="sidebar" aria-label="Primary">
        <a href="{{ url('/') }}" class="h-10 w-10 rounded-md bg-primary-500 text-white flex items-center justify-center font-bold text-sm" data-testid="brand">
            sg
        </a>
        <nav class="flex-1 flex flex-col items-center gap-1 mt-4">
            @foreach ($navItems as $item)
                <a
                    href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                    class="group h-10 w-10 rounded-md flex items-center justify-center transition-colors
                        {{ $currentRoute === $item['route'] ? 'bg-primary-50 text-primary-500' : 'text-ink-muted hover:bg-surface-soft hover:text-ink' }}"
                    title="{{ $item['label'] }}"
                    data-nav-key="{{ $item['key'] }}"
                >
                    <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
                    <span class="sr-only">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($user)
            <div class="mt-auto" data-testid="sidebar-user">
                <x-ui.avatar :name="$user->name" />
            </div>
        @endif
    </aside>

    <div
        x-show="mobileNav"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-ink/40 lg:hidden"
        @click="mobileNav = false"
        x-cloak
    ></div>

    <aside
        x-show="mobileNav"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-72 bg-surface border-r border-outline lg:hidden"
        data-testid="mobile-drawer"
        x-cloak
    >
        <div class="flex items-center justify-between px-5 py-4 border-b border-outline">
            <span class="text-sm font-semibold text-primary-500">sgCrm</span>
            <button type="button" class="text-ink-muted hover:text-ink" @click="mobileNav = false" aria-label="Close navigation">
                <x-ui.icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>
        <nav class="p-3 flex flex-col gap-1">
            @foreach ($navItems as $item)
                <a
                    href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors
                        {{ $currentRoute === $item['route'] ? 'bg-primary-50 text-primary-500' : 'text-ink hover:bg-surface-soft' }}"
                    data-nav-key="{{ $item['key'] }}"
                >
                    <x-ui.icon :name="$item['icon']" class="h-4 w-4" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="lg:pl-20">
        <header class="sticky top-0 z-20 bg-surface border-b border-outline" data-testid="topbar">
            <div class="flex items-center gap-3 px-4 sm:px-6 lg:px-8 h-14">
                <button type="button" class="lg:hidden text-ink-muted hover:text-ink" @click="mobileNav = true" aria-label="Open navigation" data-testid="mobile-toggle">
                    <x-ui.icon name="bars-3" class="h-5 w-5" />
                </button>

                <h1 class="text-base font-semibold text-ink">{{ $pageTitle ?? $title ?? config('app.name') }}</h1>

                <div class="ml-auto flex items-center gap-3">
                    @isset($actions)
                        {{ $actions }}
                    @endisset

                    @if ($user)
                        <div class="flex items-center gap-2" data-testid="topbar-user">
                            <span class="text-xs text-ink-muted hidden sm:inline">{{ $user->name }}</span>
                            <x-ui.avatar :name="$user->name" size="sm" />
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <main class="px-4 sm:px-6 lg:px-8 py-6" data-testid="app-main">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
