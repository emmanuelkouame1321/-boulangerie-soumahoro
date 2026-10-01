<x-layouts.app title="Nouveau rôle">
    <x-layout.page-header eyebrow="Administration" title="Nouveau rôle" description="Créez un rôle et attribuez-lui ses permissions." />

    <div class="max-w-4xl">
        <x-roles.form
            :action="route('settings.roles.store')"
            :permission-groups="$permissionGroups"
            submit-label="Créer le rôle"
        />
    </div>
</x-layouts.app>
