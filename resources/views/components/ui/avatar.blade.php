@props([
    'name' => null,
    'src' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
    $classes = $sizes[$size] ?? $sizes['md'];

    $initials = '';
    if ($name) {
        $parts = preg_split('/\s+/', trim($name));
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1)
            .(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        alt="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'ui-avatar inline-block rounded-full object-cover bg-surface-soft '.$classes,
        ]) }}
    />
@else
    <span
        {{ $attributes->merge([
            'class' => 'ui-avatar inline-flex items-center justify-center rounded-full bg-primary-50 text-primary-500 font-semibold '.$classes,
        ]) }}
        aria-label="{{ $name }}"
    >
        {{ $initials ?: '·' }}
    </span>
@endif
