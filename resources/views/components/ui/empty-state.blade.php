@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => 'inbox',
])

<div
    {{ $attributes->merge([
        'class' => 'ui-empty-state flex flex-col items-center justify-center text-center px-6 py-12 bg-surface border border-dashed border-outline rounded-xl',
    ]) }}
    role="status"
>
    <span class="h-12 w-12 rounded-full bg-surface-soft text-ink-muted flex items-center justify-center mb-4">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </span>

    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>

    @if ($description)
        <p class="text-sm text-ink-muted mt-1 max-w-sm">{{ $description }}</p>
    @endif

    @isset($actions)
        <div class="mt-4 flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
