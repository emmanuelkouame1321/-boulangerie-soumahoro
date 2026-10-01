<x-layouts.app title="Utilisateurs">
    <x-layout.page-header eyebrow="Administration" title="Utilisateurs">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'user-create')">Nouvel utilisateur</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Rechercher un utilisateur…" />
            <x-ui.button variant="secondary">Tous les sites</x-ui.button>
            <x-ui.button variant="secondary">Rôle</x-ui.button>
        </div>

        <x-ui.data-table :headers="['Utilisateur', 'Rôle', 'Site', 'Statut', 'Action']">
            @foreach ([
                ['Awa Kouassi', 'Caissière', 'Centre', 'Actif'],
                ['Kouassi Jean', 'Responsable production', 'Centre', 'Actif'],
                ['Admin', 'Super-Admin', 'Tous les sites', 'Actif'],
            ] as [$name, $role, $site, $status])
                <tr>
                    <td>
                        <span class="block font-bold text-ink">{{ $name }}</span>
                        <span class="block text-[11px] text-soft">{{ \Illuminate\Support\Str::slug($name).'@soumahoro.test' }}</span>
                    </td>
                    <td>{{ $role }}</td>
                    <td>{{ $site }}</td>
                    <td><x-ui.status-badge tone="success" :label="$status" :icon="false" /></td>
                    <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Compte de {{ $name }}')">Ouvrir</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="user-create" title="Nouvel utilisateur" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom complet" />
            <x-ui.form-field name="email" label="Adresse email" type="email" inputmode="email" />
            <x-ui.select name="role" :options="collect(config('boulangerie.roles'))->mapWithKeys(fn ($role, $key) => [$key => $role['label']])->all()" label="Rôle" />
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" label="Site principal" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Utilisateur créé')">Créer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
