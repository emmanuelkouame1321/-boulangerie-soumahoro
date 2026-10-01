<x-layouts.app title="Unités de mesure">
    <x-layout.page-header
        eyebrow="Paramètres"
        title="Unités de mesure"
        description="Gérez les unités (sac, kg, litre…) et leurs conversions — « 1 sac = 50 kg »."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('units.create')">Nouvelle unité</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <x-ui.card title="Unités" subtitle="Une seule source pour toutes les quantités de l’ERP" flush>
            <x-ui.data-table :headers="['Unité', 'Code', 'Nature', ['label' => 'Position', 'align' => 'right'], 'Statut', '']">
                @forelse ($units as $unit)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $unit->label }}</span>
                            <span class="block text-[11px] text-soft">{{ $unit->symbol }}</span>
                        </td>
                        <td class="numeric">{{ $unit->code }}</td>
                        <td>{{ ucfirst($unit->kind) }}</td>
                        <td class="numeric text-right">{{ $unit->position }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$unit->is_active ? 'success' : 'neutral'"
                                :label="$unit->is_active ? 'Active' : 'Inactive'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('units.edit', $unit)">Modifier</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-ui.empty-state
                                icon="scale"
                                title="Aucune unité"
                                description="Créez une première unité de mesure."
                            />
                        </td>
                    </tr>
                @endforelse
            </x-ui.data-table>
        </x-ui.card>

        <x-ui.card title="Conversions" subtitle="Le facteur est la quantité d’unité cible pour 1 unité source">
            @can('unit.manage')
                <form method="POST" action="{{ route('units.conversions.store') }}" class="mb-4 flex flex-wrap items-end gap-3">
                    @csrf

                    <div class="min-w-40">
                        <x-ui.select name="from_unit_id" label="De" :options="$unitOptions" placeholder="—" />
                    </div>
                    <div class="min-w-40">
                        <x-ui.select name="to_unit_id" label="Vers" :options="$unitOptions" placeholder="—" />
                    </div>
                    <div class="w-36">
                        <x-ui.form-field name="factor" label="Facteur" type="number" step="any" inputmode="decimal" placeholder="50" />
                    </div>

                    <x-ui.button type="submit" variant="primary" icon="plus">Ajouter</x-ui.button>
                </form>
            @endcan

            <x-ui.data-table :headers="['De', 'Vers', ['label' => 'Facteur', 'align' => 'right'], '']">
                @forelse ($conversions as $conversion)
                    <tr>
                        <td class="font-bold">{{ $conversion->fromUnit->label }}</td>
                        <td>{{ $conversion->toUnit->label }}</td>
                        <td class="numeric text-right">{{ $conversion->factor }}</td>
                        <td>
                            @can('unit.manage')
                                <form method="POST" action="{{ route('units.conversions.destroy', $conversion) }}" onsubmit="return confirm('Supprimer cette conversion ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm" icon="trash-2">Supprimer</x-ui.button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-ui.empty-state
                                icon="refresh-cw"
                                title="Aucune conversion"
                                description="Ajoutez par exemple « 1 sac = 50 kg »."
                            />
                        </td>
                    </tr>
                @endforelse
            </x-ui.data-table>
        </x-ui.card>
    </div>
</x-layouts.app>
