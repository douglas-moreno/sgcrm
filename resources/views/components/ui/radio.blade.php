@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'value' => null,
    'checked' => false,
    'error' => null,
    'state' => null,
])

@php
    $resolvedId = $id ?? ($name.'-'.$value);
    $hasError = filled($error) || $state === 'error';
    $isSuccess = $state === 'success';
    $boxClasses = match (true) {
        $hasError => 'border-danger-500 text-danger-500 focus:ring-danger-500/30',
        $isSuccess => 'border-success-500 text-success-600 focus:ring-success-500/30',
        default => 'border-outline text-primary-500 focus:ring-primary-500/30',
    };
    $labelColor = match (true) {
        $hasError => 'text-danger-600',
        $isSuccess => 'text-success-600',
        default => 'text-ink',
    };
@endphp

<label class="ui-radio inline-flex items-center gap-2 select-none {{ $attributes->get('disabled') ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
    <input
        type="radio"
        name="{{ $name }}"
        id="{{ $resolvedId }}"
        value="{{ $value }}"
        @checked($checked)
        {{ $attributes->except(['class'])->merge([
            'class' => 'h-4 w-4 border bg-surface focus:outline-none focus:ring-2 focus:ring-offset-0 '
                .$boxClasses,
        ]) }}
    />

    @if ($label)
        <span class="text-sm font-medium {{ $labelColor }}">{{ $label }}</span>
    @endif
</label>
