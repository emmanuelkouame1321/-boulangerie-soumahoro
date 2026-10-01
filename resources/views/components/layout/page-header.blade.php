@props([
    'title',
    'description' => null,
    'eyebrow' => null,
    'breadcrumbs' => [],
])

@php
    // En-tête de page de la charte caisse : surtitre optionnel, titre H1,
    // description, actions à droite. Une seule action principale.
    $context = $eyebrow;
    if ($context === null && ! empty($breadcrumbs)) {
        $context = end($breadcrumbs)['label'] ?? null;
    }
@endphp

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($context)
            <p class="text-xs font-semibold text-muted">{{ $context }}</p>
        @endif

        <h1 class="mt-0.5 text-2xl leading-tight font-bold text-ink">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1 text-[13px] text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
