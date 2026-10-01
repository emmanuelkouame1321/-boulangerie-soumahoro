@props([
    'name' => null,
    'placeholder' => 'Rechercher…',
    'value' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-9 pl-9 text-[13px]',
        'md' => 'h-11 pl-10 text-sm',
    ];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="relative min-w-44 flex-1">
    <x-ui.icon name="search" :size="16" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-soft" />

    <input
        @if ($name) name="{{ $name }}" @endif
        type="search"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-control border border-line-strong bg-surface pr-3 text-ink transition placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none '.$sizeClasses]) }}
    >
</div>
