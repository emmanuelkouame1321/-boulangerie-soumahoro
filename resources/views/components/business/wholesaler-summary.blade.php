@props([
    'wholesaler',
    'year',
    'quantity' => '0',
    'revenue' => 0,
    'orderCount' => 0,
])

@php
    use App\Support\Format;

    $regularity = $orderCount > 0 ? min(100, (int) round($orderCount / 30 * 100)) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4']) }}>
    <x-ui.stat-card
        label="Quantité {{ $year }}"
        :value="Format::stock($quantity)"
        icon="package"
        :meta="$wholesaler->usual_quantity > 0 ? 'Habituel : '.Format::stock($wholesaler->usual_quantity).' / jour' : null"
    />

    <x-ui.stat-card
        label="CA {{ $year }}"
        :value="Format::money($revenue)"
        icon="trending-up"
    />

    <x-ui.stat-card
        label="Commandes {{ $year }}"
        :value="Format::number($orderCount)"
        icon="receipt"
        :meta="$wholesaler->paymentConditionLabel() !== null ? 'Paiement : '.$wholesaler->paymentConditionLabel() : null"
    />

    <x-ui.stat-card
        label="Régularité"
        :value="Format::percent($regularity)"
        icon="calendar-check"
        :trend="$regularity >= 50 ? 'Achat régulier' : 'Achat occasionnel'"
        :trend-tone="$regularity >= 50 ? 'success' : 'muted'"
    />
</div>
