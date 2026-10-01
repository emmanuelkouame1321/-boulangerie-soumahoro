<x-layouts.app title="Boulangeries">
    <x-layout.page-header
        title="Boulangeries"
        description="Créez et gérez les sites de l’entreprise. Chaque site possède ses propres stocks."
        :breadcrumbs="[['label' => 'Accueil'], ['label' => 'Paramètres'], ['label' => 'Boulangeries']]"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('bakeries.create')">
                Nouvelle boulangerie
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="rounded-card border border-line bg-surface shadow-card">
        {{-- Design System §38 : recherche sur les listes importantes. --}}
        <form method="GET" action="{{ route('bakeries.index') }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3">
            <label for="search" class="sr-only">Rechercher une boulangerie</label>

            <div class="relative min-w-56 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-soft">
                    <x-ui.icon name="search" :size="16" />
                </span>

                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Rechercher par nom, code ou adresse…"
                    class="block w-full rounded-lg border border-line bg-surface py-2.5 pr-3 pl-9 text-sm text-ink
                        placeholder:text-soft focus:border-brand-600 focus:ring-2 focus:ring-brand-600/25 focus:outline-none"
                >
            </div>

            <x-ui.button type="submit" variant="secondary">Rechercher</x-ui.button>

            @if ($search !== '')
                <x-ui.button variant="ghost" :href="route('bakeries.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($bakeries->isEmpty())
            <div class="p-5">
                <x-ui.empty-state
                    icon="building-2"
                    title="{{ $search !== '' ? 'Aucun résultat' : 'Aucune boulangerie enregistrée' }}"
                    description="{{ $search !== ''
                        ? 'Aucune boulangerie ne correspond à « '.$search.' ».'
                        : 'Créez la première boulangerie pour commencer à enregistrer des stocks.' }}"
                >
                    <x-slot:action>
                        <x-ui.button variant="primary" icon="plus" :href="route('bakeries.create')">
                            Nouvelle boulangerie
                        </x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </div>
        @else
            {{-- Design System §25 : table desktop. --}}
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <caption class="sr-only">Liste des boulangeries</caption>

                    <thead>
                        <tr class="border-b border-line text-xs tracking-wide text-muted uppercase">
                            <th scope="col" class="px-5 py-3 font-medium">Boulangerie</th>
                            <th scope="col" class="px-5 py-3 font-medium">Responsable</th>
                            <th scope="col" class="px-5 py-3 font-medium">Statut</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Utilisateurs</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bakeries as $bakery)
                            <tr class="border-b border-line last:border-b-0 hover:bg-subtle/60">
                                <th scope="row" class="px-5 py-4 font-normal">
                                    <span class="block font-medium text-ink">{{ $bakery->name }}</span>
                                    <span class="numeric mt-0.5 block text-xs text-muted">
                                        {{ $bakery->code }}@if ($bakery->address) · {{ $bakery->address }} @endif
                                    </span>
                                </th>

                                <td class="px-5 py-4 text-muted">
                                    {{ $bakery->manager?->name ?? '—' }}
                                </td>

                                <td class="px-5 py-4">
                                    <x-ui.status-badge
                                        :tone="$bakery->status->tone()"
                                        :label="$bakery->status->label()"
                                    />
                                </td>

                                <td class="numeric px-5 py-4 text-right text-muted">
                                    {{ $bakery->users_count }}
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <x-ui.button
                                        variant="ghost"
                                        :href="route('bakeries.edit', $bakery)"
                                        :aria-label="'Modifier '.$bakery->name"
                                    >
                                        Modifier
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $bakeries->links() }}
        @endif
    </div>
</x-layouts.app>
