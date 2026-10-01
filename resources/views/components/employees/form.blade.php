@props([
    'employee' => null,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'positions' => [],
    'contractTypes' => [],
    'statuses' => [],
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="first_name" label="Prénom" :value="$employee?->first_name" :autofocus="true" required />
            <x-ui.form-field name="last_name" label="Nom" :value="$employee?->last_name" required />
            <x-ui.form-field name="phone" label="Téléphone" inputmode="tel" :value="$employee?->phone" />
            <x-ui.form-field name="address" label="Adresse" :value="$employee?->address" />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Poste et contrat</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select
                name="position"
                label="Poste"
                :options="$positions"
                :selected="$employee?->position ?? 'worker'"
                required
            />

            <x-ui.select
                name="contract_type"
                label="Type de contrat"
                :options="$contractTypes"
                :selected="$employee?->contract_type ?? 'cdi'"
                required
            />

            <x-ui.form-field
                name="base_salary"
                label="Salaire de base (FCFA)"
                type="number"
                min="0"
                step="1"
                :value="$employee?->base_salary ?? 0"
                required
            />

            <x-ui.form-field name="hired_on" label="Date d'embauche" type="date" :value="$employee?->hired_on?->format('Y-m-d')" />

            <x-ui.select
                name="status"
                label="Statut"
                :options="$statuses"
                :selected="$employee?->status?->value ?? 'active'"
                required
            />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Informations administratives</h2>

        <div class="mt-4">
            <label for="field-admin-note" class="mb-1.5 block text-xs font-bold text-ink">Observations</label>

            <textarea
                id="field-admin-note"
                name="admin_note"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('admin_note') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('admin_note')) aria-invalid="true" aria-describedby="field-admin-note-error" @endif
            >{{ old('admin_note', $employee?->admin_note) }}</textarea>

            @error('admin_note')
                <p id="field-admin-note-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('employees.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
