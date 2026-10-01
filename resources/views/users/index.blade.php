<x-layouts.app title="Utilisateurs">
    <x-layout.page-header
        eyebrow="Administration"
        title="Utilisateurs"
        description="Comptes, rôles et affectation multi-sites."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('settings.users.create')">Nouvel utilisateur</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('settings.users.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom ou email…" />
            </div>
            <x-ui.button type="submit" variant="secondary">Rechercher</x-ui.button>
            @if ($search !== '')
                <x-ui.button variant="ghost" :href="route('settings.users.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($users->isEmpty())
            <div class="p-4">
                <x-ui.empty-state icon="users-round" title="Aucun utilisateur" description="Créez le premier compte." />
            </div>
        @else
            <x-ui.data-table :headers="['Utilisateur', 'Rôles', 'Sites', '']">
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $user->name }}</span>
                            <span class="block text-[11px] text-soft">{{ $user->email }}</span>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($user->roles as $role)
                                    <x-ui.status-badge tone="info" :label="$role->name" :icon="false" />
                                @empty
                                    <span class="text-[11px] text-soft">Aucun rôle</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($user->bakeries as $bakery)
                                    <x-ui.status-badge
                                        :tone="$bakery->pivot->is_default ? 'success' : 'neutral'"
                                        :label="$bakery->code"
                                        :icon="false"
                                    />
                                @empty
                                    <span class="text-[11px] text-soft">Aucun site</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('settings.users.edit', $user)">Modifier</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">{{ $users->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
