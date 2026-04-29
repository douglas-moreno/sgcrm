@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'icon' => null,
    'type' => 'text',
])

@php
    $resolvedId = $id ?? $name;
    $hasError = filled($error);
    $stateClasses = $hasError
        ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20'
        : 'border-outline focus:border-primary-500 focus:ring-primary-500/20';
@endphp

<div class="ui-input flex flex-col gap-1" data-state="{{ $hasError ? 'error' : 'default' }}">
    @if ($label)
        <label for="{{ $resolvedId }}" class="text-xs font-medium text-ink-muted uppercase tracking-wide">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-muted">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $resolvedId }}"
            {{ $attributes->merge([
                'class' => 'block w-full h-10 rounded-md bg-surface text-sm text-ink placeholder:text-ink-muted '
                    .'border transition-colors duration-150 '
                    .'focus:outline-none focus:ring-2 '
                    .($icon ? 'pl-9 ' : 'pl-3 ').'pr-3 '
                    .$stateClasses,
            ]) }}
        />
    </div>

    @if ($hasError)
        <p class="text-xs text-danger-600" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
