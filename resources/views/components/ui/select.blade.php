@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'options' => [],
    'value' => null,
])

@php
    $resolvedId = $id ?? $name;
    $hasError = filled($error);
    $stateClasses = $hasError
        ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20'
        : 'border-outline focus:border-primary-500 focus:ring-primary-500/20';
@endphp

<div class="ui-select flex flex-col gap-1" data-state="{{ $hasError ? 'error' : 'default' }}">
    @if ($label)
        <label for="{{ $resolvedId }}" class="text-xs font-medium text-ink-muted uppercase tracking-wide">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            name="{{ $name }}"
            id="{{ $resolvedId }}"
            {{ $attributes->merge([
                'class' => 'block w-full h-10 rounded-md bg-surface text-sm text-ink pl-3 pr-9 '
                    .'border transition-colors duration-150 appearance-none '
                    .'focus:outline-none focus:ring-2 '
                    .$stateClasses,
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @selected(is_null($value))>{{ $placeholder }}</option>
            @endif

            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ink-muted">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06z" clip-rule="evenodd" />
            </svg>
        </span>
    </div>

    @if ($hasError)
        <p class="text-xs text-danger-600" role="alert">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
