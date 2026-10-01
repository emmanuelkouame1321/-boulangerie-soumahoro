<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Charte\CharteScanner;
use Illuminate\Console\Command;

/**
 * Vérifie que les vues respectent la charte BakeryOS : aucune couleur,
 * rayon ou ombre en dur — uniquement les tokens de `resources/css/app.css`.
 */
final class CharteCheckCommand extends Command
{
    protected $signature = 'charte:check';

    protected $description = 'Vérifie que les vues respectent la charte BakeryOS (tokens uniquement).';

    public function handle(CharteScanner $scanner): int
    {
        $violations = $scanner->scan();

        if ($violations === []) {
            $this->info('Charte respectée : aucune couleur, rayon ou ombre en dur.');

            return self::SUCCESS;
        }

        foreach ($violations as $violation) {
            $this->error(sprintf('%s:%d — %s', $violation['file'], $violation['line'], $violation['rule']));
            $this->line('    '.$violation['excerpt']);
        }

        $this->newLine();
        $this->error(sprintf('%d écart(s) à la charte.', count($violations)));

        return self::FAILURE;
    }
}
