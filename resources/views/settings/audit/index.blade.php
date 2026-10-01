@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Journal d’audit">
    <x-layout.page-header
        eyebrow="Traçabilité des opérations"
        title="Journal d’audit"
        description="Chaque opération critique est enregistrée : qui, quoi, objet, anciennes et nouvelles valeurs, IP, date et site. Le journal n’est jamais modifiable."
    >
        <x-slot:actions>
            <form method="POST" action="{{ route('exports.store') }}">
                @csrf
                <input type="hidden" name="kind" value="audit" />
                <input type="hidden" name="format" value="csv" />
                <input type="hidden" name="filters[search]" value="{{ $search }}" />
                <input type="hidden" name="filters[action]" value="{{ $action }}" />
                <input type="hidden" name="filters[from]" value="{{ $from }}" />
                <input type="hidden" name="filters[to]" value="{{ $to }}" />
                <x-ui.button type="submit" variant="secondary" icon="download">CSV</x-ui.button>
            </form>
            <form method="POST" action="{{ route('exports.store') }}">
                @csrf
                <input type="hidden" name="kind" value="audit" />
                <input type="hidden" name="format" value="xls" />
                <input type="hidden" name="filters[search]" value="{{ $search }}" />
                <input type="hidden" name="filters[action]" value="{{ $action }}" />
                <input type="hidden" name="filters[from]" value="{{ $from }}" />
                <input type="hidden" name="filters[to]" value="{{ $to }}" />
                <x-ui.button type="submit" variant="secondary" icon="download">Excel</x-ui.button>
            </form>
            <x-ui.button variant="secondary" icon="arrow-left" :href="route('dashboard')">Tableau de bord</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <form method="GET" action="{{ route('settings.audit.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Utilisateur, action, objet…" />
            </div>

            <div class="min-w-48">
                <x-ui.select name="action" :options="$actions" :selected="$action" placeholder="Toutes les actions" />
            </div>

            <div class="w-40">
                <x-ui.form-field name="from" label="Du" type="date" :value="$from" />
            </div>

            <div class="w-40">
                <x-ui.form-field name="to" label="Au" type="date" :value="$to" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $action !== '' || $from !== '' || $to !== '')
                <x-ui.button variant="ghost" :href="route('settings.audit.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($logs->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="file-clock"
                    title="Aucune entrée d’audit"
                    description="Les créations, modifications, validations et suppressions de produits, recettes, ventes, pertes, charges et permissions apparaîtront ici."
                />
            </div>
        @else
            <div x-data="{ detail: null }">
                <x-ui.data-table :headers="['Utilisateur', 'Action', 'Objet', 'Site', 'Date', '']">
                    @foreach ($logs as $log)
                        @php
                            $detail = [
                                'action' => $log->action,
                                'user' => $log->user_name ?? 'Compte supprimé',
                                'ip' => $log->ip_address ?? '—',
                                'date' => $log->created_at->format('d/m/Y H:i:s'),
                                'site' => $log->bakery?->name ?? 'Tous les sites',
                                'subject' => $log->subjectLabel(),
                                'old' => $log->old_values,
                                'new' => $log->new_values,
                            ];
                        @endphp
                        <tr>
                            <td>
                                <span class="block font-bold text-ink">{{ $log->user_name ?? 'Système' }}</span>
                                <span class="block text-[11px] text-soft">{{ $log->ip_address ?? '—' }}</span>
                            </td>
                            <td class="whitespace-nowrap text-ink">{{ $log->action }}</td>
                            <td class="text-soft">{{ $log->subjectLabel() }}</td>
                            <td>
                                <x-ui.status-badge tone="info" :label="$log->bakery?->name ?? 'Tous les sites'" :icon="false" />
                            </td>
                            <td class="whitespace-nowrap numeric">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                <x-ui.button
                                    variant="secondary"
                                    size="sm"
                                    data-detail="{{ json_encode($detail, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                                    @click="detail = JSON.parse($el.dataset.detail)"
                                >Détails</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $logs->links() }}
                </div>

                <x-ui.modal name="audit-detail" title="Détail de l’opération" max-width="max-w-lg">
                    <div x-show="detail" class="space-y-4" x-cloak>
                        <dl class="space-y-2">
                            <div class="flex justify-between gap-4">
                                <dt class="text-soft">Qui ?</dt>
                                <dd class="text-right font-bold text-ink" x-text="detail ? detail.user + ' • ' + detail.ip : ''"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-soft">Quoi ?</dt>
                                <dd class="text-right font-bold text-ink" x-text="detail ? detail.action : ''"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-soft">Objet</dt>
                                <dd class="text-right text-ink" x-text="detail ? detail.subject : ''"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-soft">Où ?</dt>
                                <dd class="text-right text-ink" x-text="detail ? detail.site : ''"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-soft">Quand ?</dt>
                                <dd class="text-right text-ink" x-text="detail ? detail.date : ''"></dd>
                            </div>
                        </dl>

                        <div>
                            <p class="label-caps text-muted">Anciennes valeurs</p>
                            <pre class="mt-1 overflow-auto rounded-control border border-line bg-subtle p-3 text-[12px] text-ink" x-text="detail && detail.old ? JSON.stringify(detail.old, null, 2) : '—'"></pre>
                        </div>

                        <div>
                            <p class="label-caps text-muted">Nouvelles valeurs</p>
                            <pre class="mt-1 overflow-auto rounded-control border border-line bg-subtle p-3 text-[12px] text-ink" x-text="detail && detail.new ? JSON.stringify(detail.new, null, 2) : '—'"></pre>
                        </div>
                    </div>
                </x-ui.modal>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
