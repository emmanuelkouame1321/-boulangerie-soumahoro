@props([
    'expiresAt' => null,
    'shelfLifeDays' => null,
])

@php
    use App\Support\Format;
    use Carbon\CarbonInterface;

    // Indicateur de date limite de vente (cahier §8).
    //  - si une date limite est connue (lot), elle est affichée avec son
    //    état (frais / à écouler / expiré) ;
    //  - sinon, la durée limite configurée du produit est rappelée.
    $tone = 'neutral';
    $label = null;
    $icon = null;

    if ($expiresAt !== null) {
        $date = $expiresAt instanceof CarbonInterface ? $expiresAt : \Illuminate\Support\Carbon::parse($expiresAt);
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        if ($days < 0) {
            $tone = 'danger';
            $icon = 'triangle-alert';
            $label = 'Expiré';
        } elseif ($days <= 1) {
            $tone = 'warning';
            $icon = 'triangle-alert';
            $label = 'À écouler · '.$date->format('d/m');
        } else {
            $tone = 'success';
            $icon = 'check';
            $label = 'DLC '.$date->format('d/m');
        }
    } elseif ($shelfLifeDays !== null) {
        $label = 'Vente sous '.Format::number($shelfLifeDays).' j';
    }
@endphp

@if ($label !== null)
    <x-ui.status-badge :tone="$tone" :label="$label" :icon="$icon !== null" />
@endif
