@props([
    'value' => 0,
    'tone' => 'brand',
])

@php
    $tones = [
        'brand' => 'bg-brand-500',
        'success' => 'bg-brand-500',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-context-600',
    ];
    $percent = max(0, min(100, (float) $value));
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-subtle']) }}>
    <div class="h-full rounded-full {{ $tones[$tone] ?? $tones['brand'] }}" style="width: {{ $percent }}%"></div>
</div>
