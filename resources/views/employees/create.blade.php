<x-layouts.app title="Nouvel employé">
    <x-layout.page-header
        eyebrow="Ressources humaines"
        title="Nouvel employé"
        description="Un employé appartient au site courant. Son poste et son contrat proviennent de la configuration."
    />

    <div class="max-w-3xl">
        <x-employees.form
            :employee="null"
            :action="route('employees.store')"
            submit-label="Créer l'employé"
            :positions="$positions"
            :contract-types="$contractTypes"
            :statuses="$statuses"
        />
    </div>
</x-layouts.app>
