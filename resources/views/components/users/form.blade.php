@props([
    'user',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'roleOptions' => [],
    'selectedRoles' => [],
    'bakeries' => [],
    'selectedBakeries' => [],
    'defaultBakeryId' => null,
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom complet" :value="$user->name" :autofocus="true" required />
            <x-ui.form-field name="email" label="Adresse email" type="email" inputmode="email" :value="$user->email" required />
            <x-ui.form-field
                name="password"
                label="{{ $user->exists ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe' }}"
                type="password"
                autocomplete="new-password"
                :required="! $user->exists"
                help="8 caractères minimum."
            />
            <x-ui.form-field name="password_confirmation" label="Confirmer le mot de passe" type="password" autocomplete="new-password" :required="! $user->exists" />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Rôles</h2>

        @error('roles')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            @foreach ($roleOptions as $roleName)
                <label class="flex items-center gap-2.5 text-sm">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $roleName }}"
                        @checked(in_array($roleName, old('roles', $selectedRoles), true))
                        class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
                    >
                    {{ $roleName }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Sites autorisés</h2>
        <p class="mt-1 text-xs text-muted">Un responsable ne voit que les sites qui lui sont affectés.</p>

        @error('bakeries')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror
        @error('default_bakery_id')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            @forelse ($bakeries as $bakery)
                <label class="flex items-center gap-2.5 text-sm">
                    <input
                        type="checkbox"
                        name="bakeries[]"
                        value="{{ $bakery->id }}"
                        @checked(in_array($bakery->id, old('bakeries', $selectedBakeries), true))
                        class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
                    >
                    {{ $bakery->name }}
                    <span class="text-[11px] text-soft">{{ $bakery->code }}</span>
                </label>
            @empty
                <p class="text-sm text-muted">Aucune boulangerie enregistrée.</p>
            @endforelse
        </div>

        <div class="mt-4 max-w-xs">
            <x-ui.select
                name="default_bakery_id"
                label="Site par défaut"
                :options="collect($bakeries)->pluck('name', 'id')->all()"
                :selected="old('default_bakery_id', $defaultBakeryId)"
                placeholder="— Premier site affecté —"
            />
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('settings.users.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
