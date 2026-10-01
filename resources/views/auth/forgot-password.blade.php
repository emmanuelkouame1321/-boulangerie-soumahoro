<x-layouts.auth title="Mot de passe oublié">
    <h1 class="text-xl font-semibold text-ink">Mot de passe oublié</h1>
    <p class="mt-1 text-sm text-muted">
        Indiquez votre adresse email : nous vous enverrons un lien de réinitialisation.
    </p>

    @if (session('status'))
        <x-ui.alert tone="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <x-ui.form-field
            name="email"
            label="Adresse email"
            type="email"
            autocomplete="username"
            inputmode="email"
            :autofocus="true"
            required
        />

        <x-ui.button type="submit" variant="primary" class="w-full">
            Envoyer le lien
        </x-ui.button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('login') }}" class="text-brand-700 hover:underline">Retour à la connexion</a>
    </p>
</x-layouts.auth>
