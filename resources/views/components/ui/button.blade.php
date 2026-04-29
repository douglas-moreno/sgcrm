@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary-500 text-white hover:bg-primary-900 focus-visible:ring-primary-500/40 disabled:bg-outline disabled:text-ink-muted',
        'secondary' => 'bg-primary-50 text-primary-500 hover:bg-primary-100 focus-visible:ring-primary-500/30 disabled:bg-outline disabled:text-ink-muted',
        'outline' => 'bg-surface text-primary-500 border border-primary-500 hover:bg-primary-50 focus-visible:ring-primary-500/30 disabled:border-outline disabled:text-ink-muted',
        'ghost' => 'bg-transparent text-ink hover:bg-surface-soft focus-visible:ring-primary-500/30',
        'danger' => 'bg-danger-500 text-white hover:bg-danger-600 focus-visible:ring-danger-500/40',
        'success' => 'bg-success-500 text-ink hover:bg-success-600 hover:text-white focus-visible:ring-success-500/40',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-xs gap-1.5',
        'md' => 'h-10 px-4 text-sm gap-2',
        'lg' => 'h-12 px-5 text-base gap-2.5',
    ];

    $classes = trim(
        'ui-button inline-flex items-center justify-center font-medium rounded-md '
        .'transition-colors duration-150 select-none '
        .'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-surface '
        .'disabled:cursor-not-allowed '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md'])
    );
@endphp

<button
    {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}
    @disabled($disabled || $loading)
    data-variant="{{ $variant }}"
    data-size="{{ $size }}"
>
    @if ($loading)
        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
        </svg>
    @elseif ($icon)
        <x-ui.icon :name="$icon" class="h-4 w-4" />
    @endif

    <span>{{ $slot }}</span>

    @if ($iconRight && ! $loading)
        <x-ui.icon :name="$iconRight" class="h-4 w-4" />
    @endif
</button>
