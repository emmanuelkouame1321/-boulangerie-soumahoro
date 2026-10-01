@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Ristournes">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Ristournes"
        description="Ristournes annuelles calculées depuis des paliers administrables : base éligible × taux applicable (cahier §13)."
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('rebates.index') }}" class="flex items-end gap-2">
                <div class="min-w-28">
                    <x-ui.form-field name="year" label="Exercice" type="number" min="2000" max="2100" :value="$year" />
                </div>
                <x-ui.button type="submit" variant="secondary">Afficher</x-ui.button>
            </form>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
            <x-ui.stat-card label="Théorique {{ $year }}" :value="Format::money($summary['theoretical'])" icon="calculator" />
            <x-ui.stat-card label="Validée {{ $year }}" :value="Format::money($summary['validated'])" icon="circle-check" />
            <x-ui.stat-card label="Versée {{ $year }}" :value="Format::money($summary['paid'])" icon="banknote" />
        </div>

        @if ($canManage)
            <x-ui.card title="Calculer une ristourne" subtitle="Le calcul repose sur l'historique de ventes grossistes du compte client lié.">
                <form method="POST" action="{{ route('rebates.compute') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-4">
                    @csrf

                    <x-ui.select name="wholesaler_id" label="Grossiste" :options="$wholesalerOptions" placeholder="— choisir —" required />
                    <x-ui.form-field name="period_year" label="Exercice" type="number" min="2000" max="2100" :value="$year" required />
                    <x-ui.select name="basis" label="Base de calcul" :options="$bases" :selected="config('boulangerie.rebate.default_basis')" required />
                    <x-ui.button type="submit" variant="primary" icon="calculator">Calculer</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Ristournes {{ $year }}" subtitle="Une ristourne validée ou versée n'est plus recalculée." flush>
            @if ($rebates->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="percent"
                        title="Aucune ristourne"
                        description="Lancez un calcul pour projeter la ristourne d'un grossiste à partir de ses achats de l'exercice."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Grossiste', 'Base', ['label' => 'Base éligible', 'align' => 'right'], ['label' => 'Taux', 'align' => 'right'], ['label' => 'Théorique', 'align' => 'right'], 'Statut', '']">
                    @foreach ($rebates as $rebate)
                        <tr>
                            <td class="font-bold">{{ $rebate->wholesaler?->name ?? '—' }}</td>
                            <td>{{ $rebate->basis->label() }}</td>
                            <td class="numeric text-right">{{ Format::money($rebate->base_value) }}</td>
                            <td class="numeric text-right">{{ Format::percent($rebate->rate, 3) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($rebate->effectiveValue()) }}</td>
                            <td>
                                <x-ui.status-badge :tone="$rebate->status->tone()" :label="$rebate->status->label()" :icon="false" />
                            </td>
                            <td>
                                @if ($canManage)
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @if ($rebate->status->value === 'projected')
                                            <form method="POST" action="{{ route('rebates.validate', $rebate) }}">
                                                @csrf
                                                <x-ui.button type="submit" variant="secondary" size="sm">Valider</x-ui.button>
                                            </form>
                                        @elseif ($rebate->status->value === 'validated')
                                            <form method="POST" action="{{ route('rebates.settle', $rebate) }}" class="flex items-center gap-2">
                                                @csrf
                                                <x-ui.form-field name="settled_on" label="Règlement" type="date" :value="now()->format('Y-m-d')" id="settled-{{ $rebate->getKey() }}" />
                                                <x-ui.button type="submit" variant="secondary" size="sm">Verser</x-ui.button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $rebates->links() }}
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Paliers de ristourne" subtitle="Seuils et taux administrables : aucune règle n'est codée en dur." flush>
            @if ($canManage)
                <form method="POST" action="{{ route('rebates.rules.store') }}" class="grid grid-cols-1 items-end gap-3 border-b border-line p-4 sm:grid-cols-6">
                    @csrf

                    <x-ui.form-field name="name" label="Libellé" placeholder="Palier…" required />
                    <x-ui.select name="basis" label="Base" :options="$bases" required />
                    <x-ui.select name="product_category_id" label="Famille" :options="$categories" placeholder="—" />
                    <x-ui.form-field name="min_threshold" label="Seuil bas" type="number" min="0" :value="0" required />
                    <x-ui.form-field name="max_threshold" label="Seuil haut" type="number" min="0" />
                    <x-ui.form-field name="rate" label="Taux (%)" type="number" step="0.001" min="0" max="100" :value="0" required />

                    <div class="sm:col-span-6">
                        <x-ui.button type="submit" variant="primary" icon="plus">Ajouter le palier</x-ui.button>
                    </div>
                </form>
            @endif

            @if ($rules->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="list"
                        title="Aucun palier"
                        description="Ajoutez au moins un palier pour que les ristournes puissent être calculées."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Libellé', 'Base', 'Famille', 'Seuils', ['label' => 'Taux', 'align' => 'right'], 'Statut', '']">
                    @foreach ($rules as $rule)
                        <tr>
                            <td class="font-bold">{{ $rule->name }}</td>
                            <td>{{ $rule->basis->label() }}</td>
                            <td>{{ $rule->productCategory?->name ?? '—' }}</td>
                            <td class="numeric">{{ $rule->thresholdLabel() }}</td>
                            <td class="numeric text-right">{{ Format::percent($rule->rate, 3) }}</td>
                            <td>
                                <x-ui.status-badge
                                    :tone="$rule->is_active ? 'success' : 'neutral'"
                                    :label="$rule->is_active ? 'Actif' : 'Inactif'"
                                    :icon="false"
                                />
                            </td>
                            <td>
                                @if ($canManage && $rule->is_active)
                                    <form method="POST" action="{{ route('rebates.rules.destroy', $rule) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm">Désactiver</x-ui.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
