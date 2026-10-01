@props([
    'recipe',
    'batches' => 1,
])

@php
    use App\Domain\Production\RecipeCalculator;
    use App\Support\Decimal;
    use App\Support\Format;

    // Aperçu des besoins d'une recette pour un nombre de lots : ressources,
    // quantités calculées, mode de déduction et coût estimatif.
    $calculator = app(RecipeCalculator::class);

    $requirements = $calculator->requirements($recipe, $batches);
    $yield = $calculator->theoreticalQuantity($recipe, $batches);
    $cost = $calculator->theoreticalCost($recipe, $batches);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
        <div class="min-w-0">
            <p class="text-sm font-bold text-ink">{{ $recipe->name }}</p>
            <p class="text-[11px] text-soft">
                Rendement théorique : {{ Format::stock($yield) }}
                {{ $recipe->product?->primaryUnit?->symbol ?? '' }}
                pour {{ Format::stock((string) $batches) }} lot(s)
            </p>
        </div>
        <x-ui.status-badge tone="info" :label="Format::money($cost)" :icon="false" />
    </div>

    @if (empty($requirements))
        <p class="px-4 py-4 text-[13px] text-muted">Aucune ressource configurée pour cette recette.</p>
    @else
        <x-ui.data-table :headers="['Ressource', 'Mode', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Coût', 'align' => 'right']]">
            @foreach ($requirements as $requirement)
                @php
                    $product = $recipe->items->firstWhere('product_id', $requirement['product_id'])?->product;
                @endphp
                <tr>
                    <td class="font-medium">{{ $product?->name ?? '—' }}</td>
                    <td class="text-muted">{{ $requirement['deduction_mode']->label() }}</td>
                    <td class="numeric text-right">
                        {{ Format::stock(Decimal::trim($requirement['planned_quantity'])) }}
                        {{ $product?->primaryUnit?->symbol ?? '' }}
                    </td>
                    <td class="numeric text-right font-bold">{{ Format::money($requirement['line_cost']) }}</td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif
</div>
