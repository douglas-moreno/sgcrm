@props([
    'name' => 'modal',
    'title' => null,
    'size' => 'md',
    'show' => false,
])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
    $maxWidth = $sizes[$size] ?? $sizes['md'];
@endphp

<div
    class="ui-modal fixed inset-0 z-50 {{ $show ? 'flex' : 'hidden' }} items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    data-modal="{{ $name }}"
    {{ $attributes }}
>
    <div class="absolute inset-0 bg-ink/40" data-modal-backdrop></div>

    <div class="relative w-full {{ $maxWidth }} bg-surface rounded-xl shadow-card overflow-hidden">
        @if ($title || isset($header))
            <div class="flex items-start justify-between px-6 py-4 border-b border-outline">
                <h2 class="text-base font-semibold text-ink">{{ $header ?? $title }}</h2>
                <button type="button" class="text-ink-muted hover:text-ink transition-colors" data-modal-close aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.7 4.7a1 1 0 0 1 1.4 0L10 8.6l3.9-3.9a1 1 0 1 1 1.4 1.4L11.4 10l3.9 3.9a1 1 0 0 1-1.4 1.4L10 11.4l-3.9 3.9a1 1 0 1 1-1.4-1.4L8.6 10 4.7 6.1a1 1 0 0 1 0-1.4z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-5 text-sm text-ink">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-outline bg-surface-alt">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
