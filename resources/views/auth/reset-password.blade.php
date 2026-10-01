<x-layouts.auth title="Nouveau mot de passe">
    <h1 class="text-xl font-semibold text-ink">Nouveau mot de passe</h1>
    <p class="mt-1 text-sm text-muted">Choisissez un mot de passe pour votre compte.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.form-field
            name="email"
            label="Adresse email"
            type="email"
            :value="$request->email"
            autocomplete="username"
            inputmode="email"
            required
        />

        <x-ui.form-field
            name="password"
            label="Nouveau mot de passe"
            type="password"
            autocomplete="new-password"
            help="8 caractères minimum."
            required
        />

        <x-ui.form-field
            name="password_confirmation"
            label="Confirmer le mot de passe"
            type="password"
            autocomplete="new-password"
            required
        />

        <x-ui.button type="submit" variant="primary" class="w-full">
            Réinitialiser le mot de passe
        </x-ui.button>
    </form>
</x-layouts.auth>
