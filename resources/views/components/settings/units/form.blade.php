@props([
    'unit',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

@php
    $kinds = [
        'weight' => 'Poids',
        'volume' => 'Volume',
        'container' => 'Contenant',
        'count' => 'Pièce / comptage',
    ];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Unité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="label" label="Libellé" :value="$unit->label" :autofocus="true" required />
            <x-ui.form-field name="code" label="Code interne" :value="$unit->code" help="Identifiant court et unique (ex. sack)." required />
            <x-ui.form-field name="symbol" label="Symbole" :value="$unit->symbol" help="Affiché à côté des quantités (ex. sac)." required />

            <x-ui.select name="kind" label="Nature" :options="$kinds" :selected="$unit->kind" required />

            <x-ui.form-field name="position" label="Position" type="number" inputmode="numeric" :value="$unit->position" help="Ordre d’affichage dans les listes." required />
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $unit->is_active))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Unité active
        </label>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('units.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
