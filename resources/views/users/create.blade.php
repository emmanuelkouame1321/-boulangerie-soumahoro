<x-layouts.app title="Nouvel utilisateur">
    <x-layout.page-header eyebrow="Administration" title="Nouvel utilisateur" description="Créez un compte et affectez-le à ses sites." />

    <div class="max-w-4xl">
        <x-users.form
            :user="$user"
            :action="route('settings.users.store')"
            :role-options="$roleOptions"
            :selected-roles="$selectedRoles"
            :bakeries="$bakeries"
            :selected-bakeries="$selectedBakeries"
            :default-bakery-id="$defaultBakeryId"
            submit-label="Créer le compte"
        />
    </div>
</x-layouts.app>
