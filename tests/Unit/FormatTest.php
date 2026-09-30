<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Format;
use Tests\TestCase;

class FormatTest extends TestCase
{
    public function test_money_always_carries_the_currency_label(): void
    {
        // Design System §65 : ne jamais afficher « 125000 » dans l'interface.
        $this->assertSame("125\u{202F}000 FCFA", Format::money(125000));
    }

    public function test_money_can_be_rendered_without_currency(): void
    {
        $this->assertSame("125\u{202F}000", Format::money(125000, false));
    }

    public function test_number_supports_decimal_comma(): void
    {
        $this->assertSame('7,5', Format::number(7.5, 1));
    }

    public function test_null_amounts_render_as_zero(): void
    {
        $this->assertSame('0', Format::number(null));
        $this->assertSame('0,000', Format::number(0, 3));
    }

    public function test_quantity_resolves_the_unit_symbol_from_config(): void
    {
        $this->assertSame('24 sac', Format::quantity(24, 'sack'));
        $this->assertSame('24', Format::quantity(24));
    }

    public function test_signed_ecart_keeps_the_sign(): void
    {
        $this->assertSame('-18', Format::signed(-18));
        $this->assertSame('+4', Format::signed(4));
        $this->assertSame('0', Format::signed(0));
    }
}
