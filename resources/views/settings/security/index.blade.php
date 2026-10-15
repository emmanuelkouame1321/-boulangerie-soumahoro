<x-layouts.app title="Sécurité du compte">
    <x-layout.page-header
        eyebrow="Mon compte"
        title="Sécurité du compte"
        description="Protégez votre accès avec une application d’authentification. La vérification en deux étapes est optionnelle et ne concerne que votre compte."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="arrow-left" :href="route('dashboard')">Tableau de bord</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if (session('recoveryCodes'))
        <x-ui.card
            title="Codes de secours"
            subtitle="Notez-les maintenant : ils ne seront plus affichés"
            class="mb-4"
        >
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (session('recoveryCodes') as $recoveryCode)
                    <code class="rounded-control border border-line bg-subtle px-3 py-2 text-center text-sm font-bold text-ink">{{ $recoveryCode }}</code>
                @endforeach
            </div>

            <p class="mt-3 text-[12px] text-soft">
                Chaque code remplace le code à six chiffres une seule fois, si vous n’avez plus accès à votre téléphone.
            </p>
        </x-ui.card>
    @endif

    @if ($enabled)
        <x-ui.card title="Vérification en deux étapes" subtitle="Active sur ce compte">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.status-badge tone="success" label="Activée" />
                <span class="text-[12px] text-soft">
                    {{ $recoveryCodesRemaining }} code(s) de secours sur {{ $recoveryCodeCount }} encore disponible(s)
                </span>
            </div>

            <p class="mt-3 text-[13px] text-muted">
                À chaque connexion, un code vous sera demandé après votre mot de passe. Si vous perdez votre
                téléphone, un code de secours prend le relais — il est consommé à l’usage.
            </p>

            <div class="mt-4 grid gap-4 border-t border-line pt-4 lg:grid-cols-2">
                <form method="POST" action="{{ route('settings.security.recovery-codes') }}" class="grid gap-3">
                    @csrf

                    <x-ui.form-field
                        id="password-recovery-codes"
                        name="password"
                        label="Mot de passe"
                        type="password"
                        autocomplete="current-password"
                        required
                        help="Les anciens codes cessent immédiatement de fonctionner."
                    />

                    <div>
                        <x-ui.button type="submit" variant="secondary" icon="refresh-cw">
                            Régénérer les codes de secours
                        </x-ui.button>
                    </div>
                </form>

                <form method="POST" action="{{ route('settings.security.disable') }}" class="grid gap-3">
                    @csrf
                    @method('DELETE')

                    <x-ui.form-field
                        id="password-disable"
                        name="password"
                        label="Mot de passe"
                        type="password"
                        autocomplete="current-password"
                        required
                        help="La connexion repassera en une seule étape."
                    />

                    <div>
                        <x-ui.button type="submit" variant="danger">Désactiver la vérification</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.card>
    @elseif ($pendingSecret !== null)
        <x-ui.card title="Finaliser l’activation" subtitle="La protection n’est pas encore active">
            <p class="text-[13px] text-ink">
                1. Ajoutez cette clé à votre application d’authentification (Google Authenticator, Authy, 1Password…) :
            </p>

            <div class="mt-2 rounded-control border border-line bg-subtle px-3 py-3">
                <code class="text-sm font-bold tracking-[0.12em] text-ink">{{ $formattedSecret }}</code>
            </div>

            <details class="mt-3">
                <summary class="cursor-pointer text-[12px] text-muted">Utiliser le lien de configuration</summary>
                <code class="mt-2 block rounded-control border border-line bg-subtle p-3 text-[12px] break-all text-ink">{{ $provisioningUri }}</code>
            </details>

            <p class="mt-4 text-[13px] text-ink">
                2. Saisissez le code à six chiffres affiché par l’application pour activer la protection :
            </p>

            <form method="POST" action="{{ route('settings.security.confirm') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf

                <div class="w-48">
                    <x-ui.form-field
                        id="code-confirm"
                        name="code"
                        label="Premier code"
                        autocomplete="one-time-code"
                        required
                    />
                </div>

                <x-ui.button type="submit" variant="primary" icon="shield-check">Activer</x-ui.button>
            </form>
        </x-ui.card>
    @else
        <x-ui.card title="Activer la vérification en deux étapes">
            <p class="text-[13px] text-muted">
                En plus de votre mot de passe, un code temporaire généré par votre téléphone sera demandé à chaque
                connexion. C’est la meilleure protection contre un mot de passe deviné ou réutilisé.
            </p>

            <ul class="mt-3 grid gap-1.5 text-[13px] text-ink">
                <li class="flex items-center gap-2">
                    <x-ui.icon name="check-circle" :size="16" class="text-brand-700" />
                    Fonctionne hors connexion, sans SMS.
                </li>
                <li class="flex items-center gap-2">
                    <x-ui.icon name="check-circle" :size="16" class="text-brand-700" />
                    {{ $recoveryCodeCount }} codes de secours remis à l’activation.
                </li>
                <li class="flex items-center gap-2">
                    <x-ui.icon name="check-circle" :size="16" class="text-brand-700" />
                    Désactivable à tout moment avec votre mot de passe.
                </li>
            </ul>

            <form method="POST" action="{{ route('settings.security.enable') }}" class="mt-4 grid gap-3 border-t border-line pt-4 sm:max-w-md">
                @csrf

                <x-ui.form-field
                    id="password-enable"
                    name="password"
                    label="Confirmez votre mot de passe"
                    type="password"
                    autocomplete="current-password"
                    required
                />

                <div>
                    <x-ui.button type="submit" variant="primary" icon="shield-check">
                        Activer la vérification en deux étapes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif
</x-layouts.app>
