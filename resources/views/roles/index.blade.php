@php
    /** @var array<string, array{label: string, description: string, permissions: list<string>}> $roles */
    $roles = config('boulangerie.roles', []);

    $tones = [
        'super-admin' => 'success',
        'production-manager' => 'info',
        'sales-manager' => 'info',
        'cashier' => 'neutral',
    ];
@endphp

<x-layouts.app title="Rôles & permissions">
    <x-layout.page-header eyebrow="Administration" title="Rôles & permissions">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'role-create')">Nouveau rôle</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $key => $role)
            @php
                $permissionCount = in_array('*', $role['permissions'], true)
                    ? count(config('boulangerie.permissions', []))
                    : count($role['permissions']);
            @endphp

            <x-ui.card>
                <x-slot:actions>
                    <x-ui.status-badge :tone="$tones[$key] ?? 'neutral'" :label="$permissionCount.' accès'" :icon="false" />
                </x-slot:actions>

                <p class="text-[15px] font-bold text-ink">{{ $role['label'] }}</p>
                <p class="mt-1 text-[13px] text-muted">{{ $role['description'] }}</p>

                <x-ui.button variant="secondary" size="sm" class="mt-4 w-full" onclick="window.toast('Permissions de {{ $role['label'] }}')">
                    Gérer les permissions
                </x-ui.button>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.modal name="role-create" title="Nouveau rôle" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="label" label="Nom du rôle" />
            <x-ui.form-field name="code" label="Code technique" placeholder="ex : audit-manager" />
            <x-ui.form-field name="description" label="Description" class="sm:col-span-2" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Rôle créé')">Créer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
