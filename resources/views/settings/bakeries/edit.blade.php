<x-layouts.app title="Modifier la boulangerie">
    <x-layout.page-header
        title="Modifier la boulangerie"
        :description="$bakery->name"
        :breadcrumbs="[
            ['label' => 'Accueil', 'href' => route('dashboard')],
            ['label' => 'Boulangeries', 'href' => route('bakeries.index')],
            ['label' => $bakery->name],
        ]"
    >
        <x-slot:actions>
            <x-ui.status-badge :tone="$bakery->status->tone()" :label="$bakery->status->label()" />
        </x-slot:actions>
    </x-layout.page-header>

    <div class="max-w-3xl">
        <x-settings.bakeries.form
            :bakery="$bakery"
            :managers="$managers"
            :action="route('bakeries.update', $bakery)"
            method="PUT"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
