<x-layouts.app title="Rôles & permissions">
    <x-layout.page-header
        eyebrow="Administration"
        title="Rôles & permissions"
        description="Le Super-Admin dispose d’un accès complet porté par Gate::before ; les autres rôles sont configurables."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('settings.roles.create')">Nouveau rôle</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            @php($isSuperAdmin = $role->name === 'super-admin')

            <x-ui.card>
                <x-slot:actions>
                    <x-ui.status-badge
                        :tone="$isSuperAdmin ? 'success' : 'neutral'"
                        :label="$isSuperAdmin ? $catalogueCount.' accès' : $role->permissions_count.' accès'"
                        :icon="false"
                    />
                </x-slot:actions>

                <p class="text-[15px] font-bold text-ink">{{ $role->name }}</p>
                <p class="mt-1 text-[13px] text-muted">
                    @if ($isSuperAdmin)
                        Accès complet, vision consolidée multi-sites.
                    @else
                        {{ $role->permissions_count }} permission(s) active(s).
                    @endif
                </p>

                @unless ($isSuperAdmin)
                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        class="mt-4 w-full"
                        :href="route('settings.roles.edit', $role)"
                    >
                        Gérer les permissions
                    </x-ui.button>
                @endunless
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.app>
