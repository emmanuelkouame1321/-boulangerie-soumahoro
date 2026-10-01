<x-layouts.auth title="Connexion">
    <h1 class="text-xl font-semibold text-ink">Bienvenue</h1>
    <p class="mt-1 text-sm text-muted">Connectez-vous à votre espace de gestion.</p>

    @if (session('status'))
        <x-ui.alert tone="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
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

        <x-ui.form-field
            name="password"
            label="Mot de passe"
            type="password"
            autocomplete="current-password"
            required
        />

        <label for="remember" class="flex min-h-10 cursor-pointer items-center gap-2.5 text-sm text-ink">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                value="1"
                @checked(old('remember'))
                class="size-4 rounded border-line text-brand-600 focus:ring-2 focus:ring-brand-600/25"
            >
            Se souvenir de moi
        </label>

        <x-ui.button type="submit" variant="primary" class="w-full">
            Se connecter
        </x-ui.button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">
            Mot de passe oublié ?
        </a>
    </p>
</x-layouts.auth>
