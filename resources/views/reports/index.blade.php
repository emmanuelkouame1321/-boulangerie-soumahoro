@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Rapports">
    <x-layout.page-header eyebrow="Analyse & pilotage" title="Rapports">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <div class="flex flex-wrap gap-2">
            @foreach (['Ventes', 'Stocks', 'Production', 'Pertes', 'Grossistes', 'Charges'] as $report)
                <x-ui.button :variant="$loop->first ? 'primary' : 'secondary'" onclick="window.toast('Rapport {{ $report }}')">{{ $report }}</x-ui.button>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <x-ui.button variant="secondary" icon="calendar" onclick="window.toast('Sélecteur de période')">01/10/2026 → 31/10/2026</x-ui.button>
            <x-ui.button variant="blue" icon="building-2" onclick="window.toast('Sélecteur de site')">Tous les sites</x-ui.button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div><p class="label-caps text-muted">CA</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::money(24_500_000) }}</p></div>
            <div><p class="label-caps text-muted">Ventes</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::number(8_420) }}</p></div>
            <div><p class="label-caps text-muted">Produits vendus</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::number(19_340) }}</p></div>
        </div>

        <x-ui.bar-chart :values="[35, 44, 55, 48, 70, 62, 75, 68, 84, 78, 91, 88, 100, 94, 82]" height="h-64" class="mt-5" />
    </x-ui.card>
</x-layouts.app>
