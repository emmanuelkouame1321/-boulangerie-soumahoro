<?php

declare(strict_types=1);

namespace App\Support\Charte;

use Illuminate\Support\Facades\File;

/**
 * Garde-fou de la charte BakeryOS (charte caisse).
 *
 * La charte n'a qu'une source : `resources/css/app.css` (`@theme`). Aucune
 * vue ne doit écrire une couleur, un rayon ou une ombre en dur : ce scanner
 * parcourt les vues Blade et le JavaScript et remonte toute dérive.
 *
 * Il est joué automatiquement par le test `CharteGuardTest` (donc par
 * `composer ci`) et manuellement via `php artisan charte:check`.
 */
final class CharteScanner
{
    /**
     * Règle de la charte => expression régulière détectant la dérive.
     *
     * @var array<string, string>
     */
    private const RULES = [
        'Couleur arbitraire — utiliser un token (ex. bg-brand-700)' => '/\[#[0-9a-fA-F]{3,8}\]/',
        'Palette Tailwind hors charte — utiliser brand/context/success/warning/danger/info/neutral' => '/\b(?:bg|text|border|ring|from|to|via|fill|stroke|divide|outline|placeholder|accent|caret|decoration)-(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|stone)-\d{2,3}\b/',
        'Rayon arbitraire — utiliser rounded-control / rounded-card / rounded-modal' => '/\brounded(?:-[trblxy]{1,2})?-\[/',
        'Rayon Tailwind par défaut — utiliser rounded-control / rounded-card / rounded-modal' => '/\brounded(?:-(?:xs|sm|md|lg|xl|2xl|3xl))?(?=\s|"|\'|\})|\brounded-(?:xs|sm|md|lg|xl|2xl|3xl)\b/',
        'Ombre arbitraire — utiliser shadow-card / shadow-elevated' => '/\bshadow-\[/',
        'Ombre Tailwind par défaut — utiliser shadow-card / shadow-elevated' => '/\bshadow-(?:sm|md|lg|xl|2xl|inner)\b/',
        'Fond blanc/noir en dur — utiliser bg-surface / bg-ink' => '/\bbg-(?:white|black)\b|\btext-black\b/',
        'Couleur CSS hors tokens — utiliser un token de la charte' => '/\b(?:rgb|rgba|hsl|hsla)\s*\(/',
        'Couleur en style inline — utiliser les classes de la charte' => '/style="[^"]*(?:color|background)\s*:/i',
    ];

    /**
     * @return list<array{file: string, line: int, rule: string, excerpt: string}>
     */
    public function scan(): array
    {
        $violations = [];

        foreach ($this->files() as $path) {
            /** @var list<string> $lines */
            $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lines as $index => $line) {
                foreach (self::RULES as $rule => $pattern) {
                    if (preg_match($pattern, $line) !== 1) {
                        continue;
                    }

                    $violations[] = [
                        'file' => $this->relativePath($path),
                        'line' => $index + 1,
                        'rule' => $rule,
                        'excerpt' => trim(mb_substr($line, 0, 160)),
                    ];

                    // Une seule règle signalée par ligne suffit à corriger.
                    break;
                }
            }
        }

        return $violations;
    }

    public function isClean(): bool
    {
        return $this->scan() === [];
    }

    /**
     * Vues Blade et JavaScript porteurs de classes de la charte.
     *
     * @return list<string>
     */
    private function files(): array
    {
        $paths = [];

        foreach ([resource_path('views'), resource_path('js')] as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (in_array($file->getExtension(), ['php', 'js'], true)) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        sort($paths);

        return $paths;
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
    }
}
