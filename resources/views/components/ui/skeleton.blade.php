@props([
    'lines' => 3,
    'shape' => 'text',
])

@php
    $base = 'animate-pulse bg-surface-soft rounded';
@endphp

<div {{ $attributes->merge(['class' => 'ui-skeleton flex flex-col gap-2']) }} aria-hidden="true">
    @if ($shape === 'avatar')
        <span class="{{ $base }} h-9 w-9 rounded-full"></span>
    @elseif ($shape === 'card')
        <div class="{{ $base }} h-32 w-full"></div>
    @else
        @for ($i = 0; $i < $lines; $i++)
            <span class="{{ $base }} h-3 {{ $i === $lines - 1 ? 'w-2/3' : 'w-full' }}"></span>
        @endfor
    @endif
</div>
