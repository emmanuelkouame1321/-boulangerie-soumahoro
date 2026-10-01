<x-layouts.app title="Modifier l’unité">
    <x-layout.page-header
        eyebrow="Paramètres"
        title="Modifier l’unité"
        description="{{ $unit->label }} — {{ $unit->symbol }}"
    />

    <div class="max-w-3xl">
        <x-settings.units.form
            :unit="$unit"
            :action="route('units.update', $unit)"
            method="PUT"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
