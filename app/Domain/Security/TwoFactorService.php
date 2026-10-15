<?php

declare(strict_types=1);

namespace App\Domain\Security;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Vérification en deux étapes optionnelle (cahier §31).
 *
 * Cycle de vie :
 *  1. `begin()` génère un secret TOTP et le pose sur le compte **sans**
 *     confirmer l'enrôlement : un abandon laisse le compte intact ;
 *  2. `confirm()` valide un premier code, marque la 2FA active et
 *     retourne les codes de secours en clair (montrés une seule fois) ;
 *  3. `verify()` accepte un code TOTP ou un code de secours — un code de
 *     secours consommé est retiré définitivement ;
 *  4. `disable()` remet le compte à zéro (mot de passe exigé en amont,
 *     côté contrôleur).
 *
 * Le secret et les codes de secours sont chiffrés au repos par les casts
 * du modèle `User` ; les codes de secours sont en outre hachés.
 */
final class TwoFactorService
{
    /** Clé de session mémorisant que le second facteur a été franchi. */
    public const SESSION_KEY = 'two_factor_verified';

    /** Nombre de codes de secours générés à l'activation. */
    public const RECOVERY_CODE_COUNT = 8;

    /** Clé TOTP du compte (nom affiché dans l'application d'authentification). */
    public const ISSUER = 'Boulangerie Soumahoro';

    /**
     * La 2FA est-elle active pour ce compte ?
     */
    public function enabledFor(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Démarre (ou redémarre) l'enrôlement et retourne le secret à saisir
     * dans l'application d'authentification.
     */
    public function begin(User $user): string
    {
        $secret = Totp::secret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /**
     * Secret en attente de confirmation (enrôlement commencé, non validé).
     */
    public function pendingSecret(User $user): ?string
    {
        if ($user->two_factor_confirmed_at !== null) {
            return null;
        }

        return $user->two_factor_secret;
    }

    /**
     * Confirme l'enrôlement. Retourne les codes de secours en clair, ou un
     * tableau vide si le code est invalide.
     *
     * @return list<string>
     */
    public function confirm(User $user, string $code): array
    {
        $secret = $this->pendingSecret($user);

        if ($secret === null || ! Totp::verify($secret, $code)) {
            return [];
        }

        $codes = $this->plainRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                $codes,
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    /**
     * Régénère les codes de secours d'un compte déjà protégé.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->plainRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                $codes,
            ),
        ])->save();

        return $codes;
    }

    /**
     * Un code TOTP ou un code de secours valide ? Un code de secours
     * utilisé est consommé dans la foulée.
     */
    public function verify(User $user, string $code): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        $secret = $user->two_factor_secret;

        if ($secret !== null && Totp::verify($secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    /**
     * Désactive la 2FA et efface codes comme secret.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Nombre de codes de secours encore utilisables.
     */
    public function recoveryCodesRemaining(User $user): int
    {
        $codes = $user->two_factor_recovery_codes;

        return is_array($codes) ? count($codes) : 0;
    }

    /**
     * URL à saisir dans l'application d'authentification.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        return Totp::provisioningUri($secret, $user->email, self::ISSUER);
    }

    /**
     * @return list<string>
     */
    private function plainRecoveryCodes(): array
    {
        $codes = [];

        for ($index = 0; $index < self::RECOVERY_CODE_COUNT; $index++) {
            $random = Str::upper(Str::random(10));

            $codes[] = substr($random, 0, 5).'-'.substr($random, 5);
        }

        return $codes;
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->two_factor_recovery_codes;

        if (! is_array($hashes) || $hashes === []) {
            return false;
        }

        foreach ($hashes as $index => $hash) {
            if (! is_string($hash) || ! Hash::check($code, $hash)) {
                continue;
            }

            unset($hashes[$index]);

            $user->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();

            return true;
        }

        return false;
    }
}
