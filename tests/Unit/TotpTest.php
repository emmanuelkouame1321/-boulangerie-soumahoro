<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Totp;
use Tests\TestCase;

/**
 * TOTP (RFC 6238) — cahier §31.
 *
 * Les vecteurs de référence de la RFC valident l'implémentation : c'est ce
 * qui garantit que les codes affichés par Google Authenticator, Authy ou
 * 1Password seront bien acceptés.
 */
class TotpTest extends TestCase
{
    /** Secret de test de la RFC : les 20 octets ASCII « 12345678901234567890 ». */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_it_matches_the_reference_vectors_of_the_rfc(): void
    {
        $this->assertSame('94287082', Totp::code(self::RFC_SECRET, 59, 8));
        $this->assertSame('07081804', Totp::code(self::RFC_SECRET, 1111111109, 8));
        $this->assertSame('14050471', Totp::code(self::RFC_SECRET, 1111111111, 8));
        $this->assertSame('69279037', Totp::code(self::RFC_SECRET, 2000000000, 8));
    }

    public function test_it_produces_six_digits_by_default(): void
    {
        $code = Totp::code(self::RFC_SECRET, 59);

        $this->assertSame('287082', $code);
        $this->assertSame(6, strlen($code));
    }

    public function test_a_code_is_accepted_inside_the_clock_window_only(): void
    {
        $secret = Totp::secret();
        $now = time();

        $this->assertTrue(Totp::verify($secret, Totp::code($secret, $now)));
        $this->assertTrue(Totp::verify($secret, Totp::code($secret, $now - Totp::PERIOD)));
        $this->assertTrue(Totp::verify($secret, Totp::code($secret, $now + Totp::PERIOD)));

        // Hors tolérance : horloge déréglée de trois périodes.
        $this->assertFalse(Totp::verify($secret, Totp::code($secret, $now + 3 * Totp::PERIOD)));
    }

    public function test_a_code_is_not_accepted_without_its_secret(): void
    {
        $code = Totp::code(Totp::secret(), time());

        $this->assertFalse(Totp::verify(Totp::secret(), $code));
    }

    public function test_a_non_numeric_or_empty_code_is_rejected(): void
    {
        $secret = Totp::secret();

        $this->assertFalse(Totp::verify($secret, 'abcdef'));
        $this->assertFalse(Totp::verify($secret, ''));
        $this->assertFalse(Totp::verify($secret, '  '));
    }

    public function test_a_generated_secret_is_base32_and_unique(): void
    {
        $first = Totp::secret();
        $second = Totp::secret();

        $this->assertSame(32, strlen($first));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_the_secret_can_be_presented_in_groups_of_four(): void
    {
        $this->assertSame('ABCD EFGH IJKL MNOP', Totp::format('ABCDEFGHIJKLMNOP'));
    }

    public function test_the_provisioning_uri_carries_the_secret_and_the_issuer(): void
    {
        $uri = Totp::provisioningUri('ABCDEFGHIJKLMNOP', 'vente@soumahoro.test', 'Boulangerie Soumahoro');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=ABCDEFGHIJKLMNOP', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
        $this->assertStringContainsString(rawurlencode('Boulangerie Soumahoro:vente@soumahoro.test'), $uri);
    }
}
