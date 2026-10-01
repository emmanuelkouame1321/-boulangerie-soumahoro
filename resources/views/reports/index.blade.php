@php
    use App\Support\Format;

    $maxRevenue = max(1, max(array_map(static fn ($point) => $point['revenue'], $trend ?: [['revenue' => 1]])));
    $trendValues = array_map(static fn ($point) => (int) round($point['revenue'] / $maxRevenue * 100), $trend);
@endphp

<x-layouts.app title="Rapports">
    <x-layout.page-header
        eyebrow="Analyse & pilotage"
        title="Rapports"
        description="Situation consolidée des boulangeries : ventes, stock, production, pertes, achats, grossistes, charges et paie."
    />

    <div class="space-y-4">
        <x-ui.card>
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-36">
                    <x-ui.form-field name="from" label="Du" type="date" :value="$from" />
                </div>
                <div class="min-w-36">
                    <x-ui.form-field name="to" label="Au" type="date" :value="$to" />
                </div>

                @if ($bakeryOptions !== [])
                    <div class="min-w-44">
                        <x-ui.select name="site" label="Site" :options="$bakeryOptions" :selected="$site" />
                    </div>
                @endif

                <x-ui.button type="submit" variant="secondary" icon="calendar">Appliquer</x-ui.button>
            </form>
        </x-ui.card>

        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-6">
            <x-ui.stat-card label="CA" :value="Format::money($summary['revenue'])" />
            <x-ui.stat-card label="Ventes" :value="Format::number($summary['sales'])" />
            <x-ui.stat-card label="Production" :value="Format::stock($summary['production'])" />
            <x-ui.stat-card label="Pertes" :value="Format::money($summary['losses'])" />
            <x-ui.stat-card label="Charges" :value="Format::money($summary['expenses'])" />
            <x-ui.stat-card label="Paie" :value="Format::money($summary['payroll'])" />
        </div>

        <x-ui.card title="Chiffre d'affaires — 14 derniers jours">
            <x-ui.bar-chart :values="$trendValues" height="h-48" />
        </x-ui.card>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <x-ui.card title="Rapports disponibles">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($reports as $kind => $report)
                        <a
                            href="{{ route('reports.show', ['kind' => $kind, 'from' => $from, 'to' => $to, 'site' => $site]) }}"
                            class="flex items-start gap-3 rounded-card border border-line p-3 transition hover:border-brand-500 hover:bg-subtle"
                        >
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-subtle text-muted">
                                <x-ui.icon :name="$report['icon']" :size="16" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-ink">{{ $report['label'] }}</span>
                                <span class="block text-[11px] text-soft">{{ $report['description'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="Questions du cahier §55" subtitle="Réponses immédiates, sans intervention technique." flush>
                <x-ui.data-table :headers="['Question', 'Réponse']">
                    @foreach ($questions as $entry)
                        <tr>
                            <td class="text-[12px] text-muted">{{ $entry['question'] }}</td>
                            <td class="font-bold text-ink">{{ $entry['answer'] }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
