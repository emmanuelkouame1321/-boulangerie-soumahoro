@php
    use App\Support\Format;
@endphp

<x-layouts.app title="{{ $wholesaler->name }}">
    <x-layout.page-header
        eyebrow="Grossiste"
        title="{{ $wholesaler->name }}"
        description="{{ $wholesaler->zone ? 'Zone : '.$wholesaler->zone : 'Fiche grossiste' }}"
    >
        <x-slot:actions>
            <x-ui.status-badge
                :tone="$wholesaler->is_active ? 'success' : 'neutral'"
                :label="$wholesaler->is_active ? 'Actif' : 'Inactif'"
                :icon="false"
            />

            @if ($canManage)
                <x-ui.button variant="secondary" :href="route('wholesalers.edit', $wholesaler)">Modifier</x-ui.button>
            @endif

            <x-ui.button variant="primary" :href="route('rebates.index', ['year' => $year])">Ristournes</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <x-business.wholesaler-summary
            :wholesaler="$wholesaler"
            :year="$year"
            :quantity="$aggregate['quantity']"
            :revenue="$aggregate['revenue']"
            :order-count="$orderCount"
        />

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-ui.card title="Fiche" class="lg:col-span-2">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="label-caps text-muted">Responsable</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->contact_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Téléphone</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Adresse</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Type de client</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->client_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Grille de prix</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->priceModeLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Quantité habituelle</dt>
                        <dd class="numeric mt-1 text-ink">{{ Format::stock($wholesaler->usual_quantity) }} / jour</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Conditions de paiement</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->paymentConditionLabel() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="label-caps text-muted">Date d'adhésion</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->joined_on?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="label-caps text-muted">Observations</dt>
                        <dd class="mt-1 text-ink">{{ $wholesaler->note ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-business.rebate-progress :rebate="$rebate" :year="$year" />
        </div>

        <x-ui.card title="Ventes récentes" subtitle="Les dernières ventes grossistes rattachées à ce grossiste." flush>
            @if ($recentSales->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="receipt"
                        title="Aucune vente"
                        description="Reliez un compte client à cette fiche, puis enregistrez des ventes grossistes pour voir l'historique."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Référence', 'Date', ['label' => 'Total', 'align' => 'right'], 'Statut']">
                    @foreach ($recentSales as $sale)
                        <tr>
                            <td class="font-bold">{{ $sale->reference }}</td>
                            <td>{{ $sale->sale_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($sale->total) }}</td>
                            <td>
                                <x-ui.status-badge :tone="$sale->status->tone()" :label="$sale->status->label()" :icon="false" />
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
