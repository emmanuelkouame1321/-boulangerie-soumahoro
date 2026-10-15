@props([
    'label',
    'value',
    'unit' => null,
    'trend' => null,
    'trendTone' => 'muted',
    'meta' => null,
    'icon' => null,
    'href' => null,
])

@php
    // Carte KPI de la charte caisse : libellé majuscule, valeur forte,
    // écart optionnel. Jamais surchargée. Fournir `href` rend toute la
    // carte cliquable (lien vers la liste filtrée correspondante).
    $trendTones = [
        'success' => 'text-brand-700',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'muted' => 'text-muted',
    ];

    $tag = $href !== null ? 'a' : 'div';

    $base = 'rounded-card border border-line bg-surface p-4 shadow-card';

    if ($href !== null) {
        $base .= ' block transition hover:border-brand-500 hover:shadow-elevated'
            .' focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600';
    }
@endphp

<{{ $tag }}
    @if ($href !== null) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => $base]) }}
>
    <div class="flex items-start justify-between gap-3">
        <p class="label-caps text-muted">{{ $label }}</p>
        @if ($icon)
            <x-ui.icon :name="$icon" :size="18" class="shrink-0 text-soft" />
        @endif
    </div>

    <p class="numeric mt-2 text-2xl leading-none font-extrabold text-ink">
        {{ $value }}@if ($unit)<span class="ml-1 text-sm font-medium text-muted">{{ $unit }}</span>@endif
    </p>

    @if ($trend)
        <p class="numeric mt-1.5 text-xs font-medium {{ $trendTones[$trendTone] ?? $trendTones['muted'] }}">{{ $trend }}</p>
    @endif

    @if ($meta)
        <p class="mt-1.5 text-xs text-soft">{{ $meta }}</p>
    @endif
</{{ $tag }}>
