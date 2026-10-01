@php
    use App\Support\Format;

    // Assistant de production (Design System §22, §43 et cahier §7.1).
    // Tous les calculs proviennent du contrôleur (RecipeCalculator) : la
    // vue ne fait qu'afficher. L'étape courante reflète l'avancement.
    $currentStep = $selectedRecipe === null
        ? 1
        : (($preview['can_produce'] ?? true) ? 4 : 3);
@endphp

<x-layouts.app title="Nouvelle production">
    <x-layout.page-header
        eyebrow="Production"
        title="Nouvelle production"
        description="Choisissez une recette et un nombre de lots : les besoins et le rendement théorique sont calculés automatiquement."
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('production.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" title="Vérifiez le formulaire" class="mb-4">
            {{ $errors->first() }}
        </x-ui.alert>
    @endif

    <x-ui.card class="mb-4">
        <x-business.stepper :steps="['Produit & recette', 'Engagement', 'Contrôle', 'Validation']" :current="$currentStep" />
    </x-ui.card>

    <form method="POST" action="{{ route('production.store') }}">
        @csrf

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="space-y-4 xl:col-span-2">
                {{-- Étape 1 — configuration --}}
                <x-ui.card title="1. Produit & recette" subtitle="Site et recette de production">
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                        <x-ui.select
                            name="bakery_id"
                            label="Site"
                            :options="$bakeryOptions"
                            :selected="$currentBakeryId"
                            :required="true"
                        />

                        <x-business.product-selector
                            name="recipe_id"
                            label="Recette"
                            :options="$recipeOptions"
                            :selected="$selectedRecipe?->getKey()"
                            placeholder="Choisir une recette"
                            :required="true"
                        />
                    </div>
                </x-ui.card>

                <x-ui.card title="2. Engagement" subtitle="Nombre de lots et date">
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                        <x-business.quantity-input
                            name="batch_count"
                            label="Lots / sacs"
                            :value="$batches"
                            :required="true"
                            help="Nombre de lots lancés (recette appliquée à l’unité)."
                        />

                        <x-ui.form-field
                            name="produced_on"
                            label="Date de production"
                            type="date"
                            :value="old('produced_on', now()->toDateString())"
                        />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-ui.button type="submit" variant="secondary" icon="refresh-cw" formmethod="get" formaction="{{ route('production.create') }}">
                            Calculer les besoins
                        </x-ui.button>
                    </div>
                </x-ui.card>

                {{-- Étape 2 — contrôle des besoins et du stock --}}
                @if ($selectedRecipe !== null)
                    <x-ui.card title="3. Contrôle" subtitle="Matières nécessaires et stock disponible">
                        <x-slot:actions>
                            @if ($preview['can_produce'])
                                <x-ui.status-badge tone="success" label="Stock suffisant" :icon="false" />
                            @else
                                <x-ui.status-badge tone="danger" label="Stock insuffisant" :icon="false" />
                            @endif
                        </x-slot:actions>

                        <x-ui.data-table :headers="['Ressource', 'Mode', 'Quantité', 'Disponible', 'Coût']">
                            @foreach ($preview['requirements'] as $requirement)
                                <tr>
                                    <td class="font-medium">{{ $requirement['label'] }}</td>
                                    <td class="text-muted">{{ $requirement['mode'] }}</td>
                                    <td class="numeric">
                                        {{ Format::stock($requirement['quantity']) }} {{ $requirement['unit'] ?? '' }}
                                    </td>
                                    <td>
                                        @if ($requirement['available'] === null)
                                            <span class="text-soft">Hors stock</span>
                                        @elseif ($requirement['sufficient'])
                                            <x-ui.status-badge tone="success" :label="Format::stock($requirement['available']).' '.($requirement['unit'] ?? '')" :icon="false" />
                                        @else
                                            <x-ui.status-badge tone="danger" :label="Format::stock($requirement['available']).' '.($requirement['unit'] ?? '')" :icon="false" />
                                        @endif
                                    </td>
                                    <td class="numeric font-bold">{{ Format::money($requirement['cost']) }}</td>
                                </tr>
                            @endforeach
                        </x-ui.data-table>
                    </x-ui.card>
                @endif

                {{-- Étape 3 — validation --}}
                @if ($selectedRecipe !== null)
                    <x-ui.card title="4. Validation" subtitle="Quantité réellement produite et motif d’écart">
                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <x-business.quantity-input
                                name="actual_quantity"
                                label="Quantité réellement produite"
                                :value="old('actual_quantity', $preview['theoretical_quantity'])"
                                :unit="$preview['output_unit']"
                                :required="true"
                                help="Laissez le rendement théorique si la production est conforme."
                            />

                            <x-ui.select
                                name="gap_reason"
                                label="Motif de l’écart"
                                :options="$gapReasons"
                                :selected="old('gap_reason')"
                                placeholder="Requis si l’écart est non nul"
                            />

                            <div class="sm:col-span-2">
                                <x-ui.form-field
                                    name="note"
                                    label="Observations"
                                    :value="old('note')"
                                    placeholder="Consommations, incident, remarque…"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-muted">
                                L’écart est calculé à la validation : un écart non nul exige un motif.
                            </p>
                            <x-ui.button type="submit" variant="primary" icon="check">
                                Valider la production
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @else
                    <x-ui.empty-state
                        icon="calculator"
                        title="Choisissez une recette"
                        description="Sélectionnez une recette puis « Calculer les besoins » pour voir les matières nécessaires et le rendement."
                    />
                @endif
            </div>

            {{-- Colonne de synthèse --}}
            <div class="space-y-4">
                @if ($selectedRecipe !== null)
                    <x-business.recipe-preview :recipe="$selectedRecipe" :batches="$batches" />

                    <x-business.production-summary
                        :theoretical="$preview['theoretical_quantity']"
                        :actual="old('actual_quantity', $preview['theoretical_quantity'])"
                        :cost="$preview['theoretical_cost']"
                        :unit="$preview['output_unit']"
                    />
                @else
                    <x-ui.card title="Rappel">
                        <x-ui.alert tone="info">
                            Une production validée consomme les matières, entre le produit fini et crée un lot avec sa date limite de vente.
                        </x-ui.alert>
                    </x-ui.card>
                @endif
            </div>
        </div>
    </form>
</x-layouts.app>
