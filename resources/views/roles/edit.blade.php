<x-layouts.app title="Permissions du rôle">
    <x-layout.page-header
        eyebrow="Administration"
        title="Permissions du rôle"
        description="{{ $role->name }}"
    />

    <div class="max-w-4xl">
        <x-roles.form
            :role="$role"
            :action="route('settings.roles.update', $role)"
            method="PUT"
            :permission-groups="$permissionGroups"
            :selected="$selected"
            submit-label="Enregistrer les permissions"
        />
    </div>
</x-layouts.app>
