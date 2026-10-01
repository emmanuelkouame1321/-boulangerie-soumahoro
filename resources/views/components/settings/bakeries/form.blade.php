@props([
    'bakery',
    'managers',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

@php
    use App\Enums\BakeryStatus;

    $statusOptions = [];

    foreach (BakeryStatus::cases() as $status) {
        $statusOptions[$status->value] = $status->label();
    }

    $managerOptions = collect($managers)->pluck('name', 'id')->all();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Design System §62 : formulaire en page dédiée, sections claires,
         une seule action principale. --}}
    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-semibold text-ink">Identité du site</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field
                name="name"
                label="Nom de la boulangerie"
                :value="$bakery->name"
                :autofocus="true"
                required
            />

            <x-ui.form-field
                name="code"
                label="Code interne"
                :value="$bakery->code"
                help="Identifiant court et unique, utilisé dans les rapports."
                required
            />

            <x-ui.form-field name="address" label="Adresse" :value="$bakery->address" />

            <x-ui.form-field
                name="phone"
                label="Téléphone"
                :value="$bakery->phone"
                autocomplete="tel"
                inputmode="tel"
            />

            <x-ui.select
                name="manager_id"
                label="Responsable"
                :options="$managerOptions"
                :selected="$bakery->manager_id"
                placeholder="— Non défini —"
            />

            <x-ui.select
                name="status"
                label="Statut"
                :options="$statusOptions"
                :selected="$bakery->status"
                required
            />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-semibold text-ink">Observations</h2>

        <div class="mt-4">
            <label for="field-notes" class="block text-sm font-medium text-ink">Notes internes</label>

            <textarea
                id="field-notes"
                name="notes"
                rows="4"
                class="mt-1.5 block w-full rounded-lg border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-600 focus:ring-2 focus:ring-brand-600/25 focus:outline-none
                    {{ $errors->has('notes') ? 'border-danger' : 'border-line' }}"
                @if ($errors->has('notes')) aria-invalid="true" aria-describedby="field-notes-error" @endif
            >{{ old('notes', $bakery->notes) }}</textarea>

            @error('notes')
                <p id="field-notes-error" class="mt-1.5 flex items-start gap-1.5 text-sm text-danger">
                    <x-ui.icon name="circle-alert" :size="16" class="mt-0.5 shrink-0" />
                    <span>{{ $message }}</span>
                </p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('bakeries.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
