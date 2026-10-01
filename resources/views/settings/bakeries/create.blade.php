<x-layouts.app title="Nouvelle boulangerie">
    <x-layout.page-header
        title="Nouvelle boulangerie"
        description="Créez un site indépendant avec ses propres stocks et utilisateurs."
        :breadcrumbs="[
            ['label' => 'Accueil', 'href' => route('dashboard')],
            ['label' => 'Boulangeries', 'href' => route('bakeries.index')],
            ['label' => 'Nouvelle'],
        ]"
    />

    <div class="max-w-3xl">
        <x-settings.bakeries.form
            :bakery="$bakery"
            :managers="$managers"
            :action="route('bakeries.store')"
            submit-label="Créer la boulangerie"
        />
    </div>
</x-layouts.app>
