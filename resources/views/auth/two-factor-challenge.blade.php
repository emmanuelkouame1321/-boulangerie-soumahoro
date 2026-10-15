<x-layouts.auth title="Vérification en deux étapes">
    <div class="flex items-start gap-3">
        <span class="grid size-9 shrink-0 place-items-center rounded-control bg-brand-100 text-brand-800">
            <x-ui.icon name="shield-check" :size="18" />
        </span>

        <div>
            <h1 class="text-xl font-semibold text-ink">Vérification en deux étapes</h1>
            <p class="mt-1 text-sm text-muted">
                Saisissez le code à six chiffres affiché par votre application
                d’authentification, ou l’un de vos codes de secours.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.form-field
            name="code"
            label="Code de vérification"
            autocomplete="one-time-code"
            :autofocus="true"
            required
            help="Le code change toutes les 30 secondes."
        />

        <x-ui.button type="submit" variant="primary" class="w-full">
            Vérifier et continuer
        </x-ui.button>
    </form>

    <div class="mt-5 flex items-center justify-between gap-3">
        <p class="text-xs text-soft">Code de secours accepté au format ABCDE-FGHIJ.</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted hover:underline">Se déconnecter</button>
        </form>
    </div>
</x-layouts.auth>
