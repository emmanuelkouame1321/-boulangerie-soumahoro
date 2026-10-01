@props([
    'rebate' => null,
    'year',
])

@php
    use App\Support\Format;

    $effective = $rebate?->effectiveValue() ?? 0;
    $theoretical = $rebate?->theoretical_value ?? 0;
    $percent = $theoretical > 0 ? min(100, (int) round($effective / $theoretical * 100)) : 0;
    $tone = match (true) {
        $rebate === null => 'info',
        $rebate->status->value === 'paid' => 'success',
        $rebate->status->value === 'validated' => 'success',
        default => 'info',
    };
@endphp

<x-ui.card title="Ristourne {{ $year }}" subtitle="Ristourne = base éligible × taux applicable (cahier §13).">
    @if ($rebate === null)
        <div class="flex flex-col items-start gap-2 text-sm text-muted">
            <x-ui.status-badge tone="neutral" label="Non calculée" :icon="false" />
            <p>Aucune ristourne n'est encore calculée pour cet exercice. Lancez le calcul depuis l'écran Ristournes.</p>
        </div>
    @else
        <div class="flex items-center justify-between gap-3">
            <x-ui.status-badge :tone="$rebate->status->tone()" :label="$rebate->status->label()" :icon="false" />
            <span class="text-[11px] text-soft">{{ $rebate->basis->label() }}</span>
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="label-caps text-muted">Base éligible</dt>
                <dd class="numeric mt-1 font-bold text-ink">{{ Format::money($rebate->base_value) }}</dd>
            </div>
            <div>
                <dt class="label-caps text-muted">Taux</dt>
                <dd class="numeric mt-1 font-bold text-ink">{{ Format::percent($rebate->rate, 3) }}</dd>
            </div>
            <div>
                <dt class="label-caps text-muted">Ristourne théorique</dt>
                <dd class="numeric mt-1 font-bold text-ink">{{ Format::money($rebate->theoretical_value) }}</dd>
            </div>
            <div>
                <dt class="label-caps text-muted">Validée</dt>
                <dd class="numeric mt-1 font-bold text-ink">{{ Format::money($rebate->validated_value) }}</dd>
            </div>
            <div>
                <dt class="label-caps text-muted">Versée</dt>
                <dd class="numeric mt-1 font-bold text-ink">{{ Format::money($rebate->paid_value) }}</dd>
            </div>
            <div>
                <dt class="label-caps text-muted">Règlement</dt>
                <dd class="mt-1 font-bold text-ink">{{ $rebate->settled_on?->format('d/m/Y') ?? '—' }}</dd>
            </div>
        </dl>

        <div class="mt-4">
            <div class="mb-1.5 flex items-center justify-between text-[11px] text-soft">
                <span>Progression</span>
                <span class="numeric">{{ $percent }} %</span>
            </div>
            <x-ui.progress :value="$percent" :tone="$tone" />
        </div>

        @if ($rebate->rule !== null)
            <p class="mt-3 text-[11px] text-soft">Palier : {{ $rebate->rule->name }} ({{ $rebate->rule->thresholdLabel() }})</p>
        @endif
    @endif
</x-ui.card>
