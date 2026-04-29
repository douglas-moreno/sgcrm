@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $variants = [
        'info' => 'bg-primary-50 text-primary-900 border-primary-200',
        'success' => 'bg-success-100 text-success-600 border-success-500',
        'warning' => 'bg-warning-100 text-warning-600 border-warning-500',
        'danger' => 'bg-danger-100 text-danger-600 border-danger-500',
    ];
    $classes = $variants[$variant] ?? $variants['info'];
@endphp

<div
    class="ui-toast flex items-start gap-3 rounded-md border px-4 py-3 text-sm {{ $classes }}"
    role="status"
    data-variant="{{ $variant }}"
    {{ $attributes }}
>
    <div class="flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
</div>
