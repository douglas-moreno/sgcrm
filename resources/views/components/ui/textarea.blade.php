@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'rows' => 4,
])

@php
    $resolvedId = $id ?? $name;
    $hasError = filled($error);
    $stateClasses = $hasError
        ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20'
        : 'border-outline focus:border-primary-500 focus:ring-primary-500/20';
@endphp

<div class="ui-textarea flex flex-col gap-1" data-state="{{ $hasError ? 'error' : 'default' }}">
    @if ($label)
        <label for="{{ $resolvedId }}" class="text-xs font-medium text-ink-muted uppercase tracking-wide">
            {{ $label }}
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $resolvedId }}"
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-md bg-surface text-sm text-ink placeholder:text-ink-muted px-3 py-2 '
                .'border transition-colors duration-150 '
                .'focus:outline-none focus:ring-2 '
                .$stateClasses,
        ]) }}
    >{{ $slot }}</textarea>

    @if ($hasError)
        <p class="text-xs text-danger-600" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
