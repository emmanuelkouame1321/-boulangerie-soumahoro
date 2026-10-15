<?php

declare(strict_types=1);

namespace Tests\Feature\Design;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Le composant `x-ui.data-table` doit rester lisible sur téléphone : sous
 * `md`, chaque ligne devient une carte (styles dans `resources/css/app.css`).
 * Faute de pouvoir étiqueter le contenu du slot en Blade, les libellés de
 * colonnes sont transmis en variables CSS consommées par `td::before`.
 */
class ResponsiveTableTest extends TestCase
{
    public function test_it_exposes_the_card_layout_and_column_labels(): void
    {
        $html = Blade::render(
            '<x-ui.data-table :headers="$headers"><tr><td>Baguette</td><td>Comptoir</td><td class="numeric text-right">3</td></tr></x-ui.data-table>',
            ['headers' => ['Produit', 'Client', ['label' => 'Articles', 'align' => 'right']]]
        );

        $this->assertStringContainsString('data-card-table', $html);
        $this->assertStringContainsString("--dt-label-2: 'Client'", $html);
        $this->assertStringContainsString("--dt-label-3: 'Articles'", $html);
    }

    public function test_the_card_layout_can_be_disabled(): void
    {
        $html = Blade::render(
            '<x-ui.data-table :stack="false" :headers="$headers"><tr><td>Baguette</td></tr></x-ui.data-table>',
            ['headers' => ['Produit']]
        );

        $this->assertStringNotContainsString('data-card-table', $html);
        $this->assertStringNotContainsString('--dt-label', $html);
    }

    public function test_labels_are_escaped_for_the_css_string(): void
    {
        $html = Blade::render(
            '<x-ui.data-table :headers="$headers"><tr><td>Baguette</td><td>x</td></tr></x-ui.data-table>',
            ['headers' => ['Produit', "Chiffre d'affaires"]]
        );

        $this->assertStringContainsString("--dt-label-2: 'Chiffre d\\'affaires'", $html);
    }
}
