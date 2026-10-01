<x-layouts.app title="Modifier l’utilisateur">
    <x-layout.page-header eyebrow="Administration" title="Modifier l’utilisateur" description="{{ $user->name }} — {{ $user->email }}" />

    <div class="max-w-4xl">
        <x-users.form
            :user="$user"
            :action="route('settings.users.update', $user)"
            method="PUT"
            :role-options="$roleOptions"
            :selected-roles="$selectedRoles"
            :bakeries="$bakeries"
            :selected-bakeries="$selectedBakeries"
            :default-bakery-id="$defaultBakeryId"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
