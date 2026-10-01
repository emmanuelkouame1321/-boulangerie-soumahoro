<x-layouts.app title="Nouvelle unité">
    <x-layout.page-header
        eyebrow="Paramètres"
        title="Nouvelle unité de mesure"
        description="Ajoutez une unité utilisée par les produits et les recettes."
    />

    <div class="max-w-3xl">
        <x-settings.units.form
            :unit="$unit"
            :action="route('units.store')"
            submit-label="Créer l’unité"
        />
    </div>
</x-layouts.app>
