<x-layouts.app title="Paramètres">
    <x-layout.page-header
        eyebrow="Administration"
        title="Paramètres"
        description="Identité du site courant, ventes, production et notifications."
    >
        <x-slot:actions>
            @can('viewAny', App\Models\Bakery::class)
                <x-ui.button variant="secondary" icon="building-2" :href="route('bakeries.index')">
                    Gérer les sites
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        {{-- Identité du site (cahier §19.2, ADR-033) : la fiche du site
             courant, modifiable par le Super-Admin uniquement. Le site visé
             vient du contexte de site, jamais du formulaire. --}}
        <x-ui.card title="Informations du site" subtitle="Nom, code, coordonnées et logo du site courant.">
            @if ($bakery === null)
                <x-ui.empty-state
                    icon="building-2"
                    title="Aucun site sélectionné"
                    description="Choisissez une boulangerie dans le sélecteur de site de la barre supérieure pour afficher et modifier ses informations."
                >
                    <x-slot:action>
                        @can('viewAny', App\Models\Bakery::class)
                            <x-ui.button variant="primary" icon="building-2" :href="route('bakeries.index')">
                                Voir les boulangeries
                            </x-ui.button>
                        @endcan
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                @can('update', $bakery)
                    <form method="POST" action="{{ route('settings.site.update') }}" class="space-y-4" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-card border border-line bg-subtle">
                                @if ($bakery->logoUrl() !== null)
                                    <img src="{{ $bakery->logoUrl() }}" alt="Logo de {{ $bakery->name }}" class="size-full object-cover">
                                @else
                                    <span class="text-xl font-extrabold text-brand-700">{{ $bakery->initial() }}</span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <label for="field-logo" class="mb-1.5 block text-xs font-bold text-ink">Logo de la boulangerie</label>

                                <input
                                    id="field-logo"
                                    name="logo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    class="block w-full text-sm text-ink file:mr-3 file:rounded-control file:border file:border-line file:bg-subtle file:px-3 file:py-2 file:text-xs file:font-bold file:text-ink hover:file:bg-line focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
                                >

                                <p class="mt-1.5 text-[11px] text-soft">
                                    JPEG, PNG, WebP ou GIF — 4 Mo maximum. Une image carrée convient mieux :
                                    elle devient l’icône de l’onglet du navigateur et la marque de la barre latérale.
                                </p>

                                @error('logo')
                                    <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
                                @enderror

                                @if ($bakery->logoUrl() !== null)
                                    <label class="mt-3 flex items-center gap-2.5 text-sm">
                                        <input type="hidden" name="remove_logo" value="0">
                                        <input
                                            type="checkbox"
                                            name="remove_logo"
                                            value="1"
                                            class="size-4 rounded-control border-line-strong text-danger focus:ring-2 focus:ring-brand-100"
                                        >
                                        Supprimer le logo
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <x-ui.form-field
                                name="name"
                                label="Nom de la boulangerie"
                                :value="$bakery->name"
                                required
                            />

                            <x-ui.form-field
                                name="code"
                                label="Code interne"
                                :value="$bakery->code"
                                help="Identifiant court et unique, utilisé dans les rapports."
                                required
                            />

                            <x-ui.form-field
                                name="phone"
                                label="Téléphone"
                                :value="$bakery->phone"
                                autocomplete="tel"
                                inputmode="tel"
                            />

                            <x-ui.form-field name="address" label="Adresse" :value="$bakery->address" />
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-[11px] text-soft">
                                Devise : {{ config('boulangerie.currency.label') }} — paramètre global de l’application.
                                Le statut, le responsable et les notes du site se règlent dans
                                <a href="{{ route('bakeries.index') }}" class="font-bold text-context-700 underline">Gérer les sites</a>.
                            </p>

                            <x-ui.button type="submit" variant="primary">Enregistrer les informations</x-ui.button>
                        </div>
                    </form>
                @else
                    <dl class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                        <div>
                            <dt class="label-caps text-muted">Nom de la boulangerie</dt>
                            <dd class="mt-1 text-sm font-bold text-ink">{{ $bakery->name }}</dd>
                        </div>
                        <div>
                            <dt class="label-caps text-muted">Code interne</dt>
                            <dd class="numeric mt-1 text-sm text-ink">{{ $bakery->code }}</dd>
                        </div>
                        <div>
                            <dt class="label-caps text-muted">Téléphone</dt>
                            <dd class="numeric mt-1 text-sm text-ink">{{ $bakery->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="label-caps text-muted">Adresse</dt>
                            <dd class="mt-1 text-sm text-ink">{{ $bakery->address ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="label-caps text-muted">Statut</dt>
                            <dd class="mt-1">
                                <x-ui.status-badge :tone="$bakery->status->tone()" :label="$bakery->status->label()" />
                            </dd>
                        </div>
                    </dl>

                    <x-ui.alert tone="info" class="mt-4">
                        Seul un administrateur peut modifier les informations d’une boulangerie.
                    </x-ui.alert>
                @endcan
            @endif
        </x-ui.card>

        <x-ui.card title="Vente">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <x-ui.select name="cash" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Paiement espèces" />
                <x-ui.select name="mobile_money" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Mobile Money" />
                <x-ui.select name="scanner" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Scanner code-barres" />
                <x-ui.select name="printing" :options="['Après validation' => 'Après validation', 'Manuel' => 'Manuel', 'Désactivé' => 'Désactivé']" label="Impression ticket" />
            </div>
        </x-ui.card>

        <x-ui.card title="Production">
            <div class="grid gap-3">
                @foreach (['FEFO pour produits périssables', 'Bloquer production si matière insuffisante', 'Créer automatiquement les lots', 'Alerter sur écart de rendement'] as $option)
                    <label class="flex items-center gap-2.5 text-[13px]">
                        <input type="checkbox" checked class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100">
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Notifications">
            <div class="grid gap-3">
                @foreach (['Stock critique', 'Produit proche péremption', 'Commande en retard', 'Production insuffisante'] as $option)
                    <label class="flex items-center justify-between gap-3 text-[13px]">
                        {{ $option }}
                        <input type="checkbox" checked class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100">
                    </label>
                @endforeach
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
