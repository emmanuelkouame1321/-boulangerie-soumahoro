<x-layouts.app title="Préférences de notification">
    <x-layout.page-header
        eyebrow="Administration"
        title="Préférences de notification"
        description="Canaux par type d’alerte, pour la boulangerie sélectionnée. L’interne pilote l’écran Notifications ; l’email part en file d’attente vers les utilisateurs affectés au site ayant la permission de voir les alertes."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="bell" :href="route('notifications.index')">Centre d’alertes</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" :title="session('status')" />
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" :title="$errors->first()" />
    @endif

    @if ($bakery === null)
        <x-ui.empty-state
            icon="building-2"
            title="Aucune boulangerie sélectionnée"
            description="Sélectionnez un site dans le sélecteur de la barre supérieure pour configurer ses notifications."
        />
    @else
        <form method="POST" action="{{ route('settings.notifications.update') }}">
            @csrf

            <x-ui.card flush>
                <x-ui.data-table :headers="['Alerte', ['label' => 'Interne', 'align' => 'center'], ['label' => 'Email', 'align' => 'center']]">
                    @foreach ($types as $type)
                        <tr>
                            <td>
                                <span class="block font-bold text-ink">{{ $type->label() }}</span>
                                <span class="block text-[11px] text-soft">{{ $type->value }}</span>
                            </td>
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    name="internal[{{ $type->value }}]"
                                    value="1"
                                    @checked($channels[$type->value]['internal'] ?? false)
                                    class="size-4 accent-brand-700"
                                    aria-label="Notification interne — {{ $type->label() }}"
                                />
                            </td>
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    name="email[{{ $type->value }}]"
                                    value="1"
                                    @checked($channels[$type->value]['email'] ?? false)
                                    class="size-4 accent-brand-700"
                                    aria-label="Email — {{ $type->label() }}"
                                />
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="flex items-center justify-between gap-3 border-t border-line p-4">
                    <p class="text-[12px] text-soft">
                        Site : <span class="font-bold text-ink">{{ $bakery->name }}</span>
                    </p>
                    <x-ui.button type="submit">Enregistrer</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif
</x-layouts.app>
