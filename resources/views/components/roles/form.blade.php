@props([
    'role' => null,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'permissionGroups' => [],
    'selected' => [],
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Rôle</h2>

        <div class="mt-4 max-w-md">
            <x-ui.form-field
                name="name"
                label="Code du rôle"
                :value="$role?->name"
                help="Minuscules, chiffres et tirets (ex. sales-manager)."
                :autofocus="true"
                required
            />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Permissions</h2>

        @error('permissions')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="mt-4 space-y-5">
            @foreach ($permissionGroups as $module => $permissions)
                <div>
                    <p class="label-caps mb-2.5 text-muted">{{ $module }}</p>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2.5 text-sm">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    @checked(in_array($permission, old('permissions', $selected), true))
                                    class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
                                >
                                {{ $permission }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('settings.roles.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
