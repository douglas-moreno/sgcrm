@props([
    'name',
    'variant' => 'outline',
])

<x-heroicons
    :name="$name"
    :variant="$variant === 'solid' ? 'solid' : 'outline'"
    {{ $attributes->merge(['class' => 'inline-block h-5 w-5']) }}
/>
