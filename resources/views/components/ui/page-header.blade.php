@props([
    'title',
    'subtitle' => null,
])

<div
    {{ $attributes->merge([
        'class' => 'ui-page-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6',
    ]) }}
>
    <div>
        <h1 class="text-xl font-semibold text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm text-ink-muted mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2 flex-wrap">{{ $actions }}</div>
    @endisset
</div>
