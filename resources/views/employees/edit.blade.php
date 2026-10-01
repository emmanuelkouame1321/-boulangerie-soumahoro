<x-layouts.app title="Modifier un employé">
    <x-layout.page-header
        eyebrow="Ressources humaines"
        title="Modifier {{ $employee->fullName() }}"
        description="La fiche employé conserve le site d'origine."
    />

    <div class="max-w-3xl">
        <x-employees.form
            :employee="$employee"
            :action="route('employees.update', $employee)"
            method="PUT"
            submit-label="Enregistrer les modifications"
            :positions="$positions"
            :contract-types="$contractTypes"
            :statuses="$statuses"
        />
    </div>
</x-layouts.app>
