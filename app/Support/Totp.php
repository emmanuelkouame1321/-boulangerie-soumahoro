<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Codes à usage unique basés sur le temps — TOTP, RFC 6238 (cahier §31).
 *
 * Implémentation volontairement sans dépendance : HMAC-SHA1 sur un
 * compteur de période de 30 s, secret Base32 (RFC 4648) compatible avec
 * Google Authenticator, Authy, 1Password et FreeOTP.
 *
 * Le secret est toujours manipulé en Base32 (jamais en binaire) : c'est
 * le format que les applications d'authentification attendent, et il
 * reste lisible pour une saisie manuelle.
 */
final class Totp
{
    /** Alphabet Base32 sans caractères ambigus (RFC 4648). */
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Durée de validité d'un code, en secondes. */
    public const PERIOD = 30;

    /** Nombre de chiffres d'un code. */
    public const DIGITS = 6;

    /**
     * Nouveau secret aléatoire de 160 bits, en Base32 (32 caractères).
     */
    public static function secret(int $bytes = 20): string
    {
        return self::encodeBase32(random_bytes($bytes));
    }

    /**
     * Code à un instant donné (par défaut : maintenant).
     */
    public static function code(string $secret, ?int $timestamp = null, int $digits = self::DIGITS): string
    {
        $counter = intdiv($timestamp ?? time(), self::PERIOD);

        // HMAC-SHA1 du compteur sur 8 octets big-endian (RFC 4226).
        $hash = hash_hmac('sha1', pack('J', $counter), self::decodeBase32($secret), true);

        // Troncature dynamique : les 4 derniers bits désignent l'offset.
        $offset = ord($hash[19]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $modulo = 10 ** $digits;

        return str_pad((string) ($value % $modulo), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Le code est-il valide pour ce secret ? La tolérance de `$window`
     * périodes de part et d'autre absorbe les petites dérives d'horloge
     * entre le serveur et le téléphone.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if ($code === '' || ! ctype_digit($code)) {
            return false;
        }

        $now = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::code($secret, $now + $offset * self::PERIOD), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * URI `otpauth://` à saisir dans l'application d'authentification
     * (ou à convertir en QR par le client).
     */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
    }

    /**
     * Secret présenté par groupes de 4 caractères, pour une saisie manuelle
     * confortable.
     */
    public static function format(string $secret): string
    {
        return trim((string) chunk_split($secret, 4, ' '));
    }

    private static function encodeBase32(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private static function decodeBase32(string $secret): string
    {
        $normalised = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');

        $bits = '';

        foreach (str_split($normalised) as $character) {
            $index = strpos(self::ALPHABET, $character);

            if ($index === false) {
                continue;
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }

            $binary .= chr((int) bindec($chunk));
        }

        return $binary;
    }
}
