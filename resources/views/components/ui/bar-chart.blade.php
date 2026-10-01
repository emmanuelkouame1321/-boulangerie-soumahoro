@props([
    'values' => [],
    'height' => 'h-56',
])

{{-- Histogramme léger : barres CSS, aucune dépendance graphique.
     Les valeurs sont des pourcentages (0-100). --}}
<div {{ $attributes->merge(['class' => 'flex items-end gap-2 border-t border-line px-2 pt-4 pb-1 '.$height]) }}>
    @foreach ($values as $value)
        <div
            class="min-w-2.5 flex-1 rounded-t-sm {{ $loop->iteration % 3 === 0 ? 'bg-brand-500' : 'bg-brand-200' }}"
            style="height: {{ max(4, min(100, (float) $value)) }}%"
        ></div>
    @endforeach
</div>
