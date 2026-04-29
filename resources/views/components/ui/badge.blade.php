@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'neutral' => 'bg-surface-soft text-ink-muted',
        'primary' => 'bg-primary-50 text-primary-500',
        'success' => 'bg-success-100 text-success-600',
        'warning' => 'bg-warning-100 text-warning-600',
        'danger' => 'bg-danger-100 text-danger-600',
        'accent' => 'bg-accent-100 text-accent-600',
    ];
    $sizes = [
        'sm' => 'text-[10px] px-2 py-0.5',
        'md' => 'text-xs px-2.5 py-1',
        'lg' => 'text-sm px-3 py-1.5',
    ];
    $classes = ($variants[$variant] ?? $variants['neutral']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

<span
    {{ $attributes->merge([
        'class' => 'ui-badge inline-flex items-center gap-1 rounded-full font-medium '.$classes,
    ]) }}
    data-variant="{{ $variant }}"
>
    {{ $slot }}
</span>
