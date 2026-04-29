@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'md',
])

@php
    $paddings = [
        'none' => '',
        'sm' => 'p-3',
        'md' => 'p-5',
        'lg' => 'p-7',
    ];
    $bodyPadding = $paddings[$padding] ?? $paddings['md'];
@endphp

<div
    {{ $attributes->merge([
        'class' => 'ui-card bg-surface border border-outline rounded-xl shadow-card',
    ]) }}
>
    @if ($title || $subtitle || isset($header))
        <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-outline">
            <div>
                @if ($title)
                    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-ink-muted mt-0.5">{{ $subtitle }}</p>
                @endif
                {{ $header ?? '' }}
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $bodyPadding }}">{{ $slot }}</div>

    @isset($footer)
        <div class="px-5 py-3 border-t border-outline bg-surface-alt rounded-b-xl">
            {{ $footer }}
        </div>
    @endisset
</div>
