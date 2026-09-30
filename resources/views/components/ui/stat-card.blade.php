@props([
    'label',
    'value',
    'unit' => null,
    'trend' => null,
    'trendTone' => 'muted',
    'meta' => null,
    'icon' => null,
])

@php
    // Design System §14 — KPI card, hauteur 132–156px, jamais surchargée.
    $trendTones = [
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'muted' => 'text-muted',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-5 shadow-card']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ $label }}</p>
        @if ($icon)
            <x-ui.icon :name="$icon" :size="18" class="shrink-0 text-soft" />
        @endif
    </div>

    <p class="numeric mt-3 text-3xl leading-none font-semibold text-ink">
        {{ $value }}@if ($unit)<span class="ml-1 text-base font-medium text-muted">{{ $unit }}</span>@endif
    </p>

    @if ($trend)
        <p class="numeric mt-2 text-sm font-medium {{ $trendTones[$trendTone] ?? $trendTones['muted'] }}">{{ $trend }}</p>
    @endif

    @if ($meta)
        <p class="mt-2 text-xs text-muted">{{ $meta }}</p>
    @endif
</div>
