@props([
    'theoretical',
    'actual' => null,
    'gap' => null,
    'cost' => 0,
    'unit' => null,
])

@php
    use App\Support\Format;

    // Synthèse du rendement d'une production : théorique, réel, écart et
    // coût. L'écart est neutre s'il est nul, sinon signalé.
    $hasGap = $gap !== null && bccomp((string) $gap, '0', 4) !== 0;
    $gapTone = $hasGap ? (bccomp((string) $gap, '0', 4) < 0 ? 'warning' : 'info') : 'success';
    $gapLabel = $hasGap ? Format::signed($gap) : 'Aucun écart';
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-3.5 xl:grid-cols-4']) }}>
    <x-ui.stat-card label="Rendement théorique" :value="Format::stock($theoretical)" :unit="$unit" icon="calculator" />
    <x-ui.stat-card label="Production réelle" :value="$actual !== null ? Format::stock($actual) : '—'" :unit="$unit" icon="package-check" />
    <x-ui.stat-card
        label="Écart"
        :value="$gap !== null ? Format::signed($gap) : '—'"
        :unit="$unit"
        :trend="$gapLabel"
        :trend-tone="$gapTone"
        icon="scale"
    />
    <x-ui.stat-card label="Coût réel" :value="Format::money($cost)" trend="Matières et ressources" icon="coins" />
</div>
