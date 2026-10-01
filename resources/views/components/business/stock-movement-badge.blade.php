@props([
    'type',
])

@php
    use App\Enums\StockMovementType;

    // Badge d'un mouvement de stock : le sens vient du type (cf.
    // StockMovementType::sign()), jamais d'une couleur codée en dur.
    $movementType = $type instanceof StockMovementType
        ? $type
        : StockMovementType::tryFrom((string) $type);

    $increases = $movementType?->increasesStock() ?? true;

    $tone = $increases ? 'success' : 'neutral';
    $label = $movementType?->label() ?? '—';
@endphp

<x-ui.status-badge :tone="$tone" :label="$label" :icon="$increases" />
