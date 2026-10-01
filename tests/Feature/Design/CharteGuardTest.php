<?php

declare(strict_types=1);

namespace Tests\Feature\Design;

use App\Support\Charte\CharteScanner;
use Tests\TestCase;

/**
 * Empêche toute dérive visuelle : les vues ne doivent utiliser que les
 * tokens de la charte BakeryOS (couleurs, rayons, ombres). Joué par
 * `composer ci`, donc à chaque exécution des tests.
 */
class CharteGuardTest extends TestCase
{
    public function test_views_only_use_charte_tokens(): void
    {
        $violations = (new CharteScanner)->scan();

        $this->assertSame([], $violations, $this->message($violations));
    }

    /**
     * @param  list<array{file: string, line: int, rule: string, excerpt: string}>  $violations
     */
    private function message(array $violations): string
    {
        $lines = ['Écarts à la charte BakeryOS :'];

        foreach ($violations as $violation) {
            $lines[] = sprintf(
                '  - %s:%d — %s',
                $violation['file'],
                $violation['line'],
                $violation['rule']
            );
            $lines[] = '        '.$violation['excerpt'];
        }

        return implode(PHP_EOL, $lines);
    }
}
