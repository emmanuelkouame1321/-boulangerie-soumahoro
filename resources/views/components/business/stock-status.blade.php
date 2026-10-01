@props([
    'status' => 'available',
])

@php
    // Statut de stock : libellé et couleur viennent exclusivement de
    // config('boulangerie.statuses.stock'). Jamais de couleur d'état seule.
    $definition = config("boulangerie.statuses.stock.{$status}", [
        'label' => 'Inconnu',
        'tone' => 'neutral',
    ]);
@endphp

<x-ui.status-badge :tone="$definition['tone']" :label="$definition['label']" />
