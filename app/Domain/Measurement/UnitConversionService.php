<?php

declare(strict_types=1);

namespace App\Domain\Measurement;

use App\Exceptions\UnitConversionException;
use App\Models\Unit;
use App\Models\UnitConversion;

/**
 * Conversions d'unités (cahier §4.3) — « 1 sac de farine = 50 kg ».
 *
 * La conversion est dérivée des lignes `unit_conversions` (configurables,
 * jamais codées en dur). Elle gère :
 *  - la conversion directe (1 sac → 50 kg) ;
 *  - la conversion inverse (1 kg → 1/50 sac) ;
 *  - les chaînes (1 sac → 50 kg → 50 000 g) par parcours de graphe borné.
 *
 * Les quantités sont calculées en décimal exact (BCMath) : jamais de
 * `float`, la précision d'une quantité métier ne doit pas dépendre de la
 * représentation binaire.
 */
final class UnitConversionService
{
    /** Nombre de décimales conservées pour un facteur inverse. */
    private const FACTOR_SCALE = 16;

    /** Profondeur maximale d'un chemin de conversion (garde-fou anti-boucle). */
    private const MAX_DEPTH = 4;

    /**
     * Facteur exact (chaîne décimale) transformant 1 unité source en unités
     * cibles. Vaut « 1 » pour une unité identique.
     *
     * @throws UnitConversionException
     */
    public function factor(Unit $from, Unit $to): string
    {
        if ($from->getKey() === $to->getKey()) {
            return '1';
        }

        $factor = $this->search($this->graph(), (int) $from->getKey(), (int) $to->getKey());

        if ($factor === null) {
            throw UnitConversionException::between($from, $to);
        }

        return $factor;
    }

    public function canConvert(Unit $from, Unit $to): bool
    {
        if ($from->getKey() === $to->getKey()) {
            return true;
        }

        return $this->search($this->graph(), (int) $from->getKey(), (int) $to->getKey()) !== null;
    }

    /**
     * Convertit une quantité d'une unité vers une autre.
     *
     * @return string quantité en unité cible (chaîne décimale, zéros inutiles retirés)
     *
     * @throws UnitConversionException
     */
    public function convert(int|float|string $quantity, Unit $from, Unit $to, int $scale = 8): string
    {
        $factor = $this->factor($from, $to);

        return $this->trim(bcmul($this->normalize($quantity), $factor, $scale));
    }

    /**
     * Graphe de conversion : pour chaque unité, la liste des unités
     * atteignables avec le facteur exact (direct puis inverse).
     *
     * @return array<int, list<array{to: int, factor: string}>>
     */
    private function graph(): array
    {
        $graph = [];

        /** @var iterable<UnitConversion> $conversions */
        $conversions = UnitConversion::query()->get();

        foreach ($conversions as $conversion) {
            $factor = (string) $conversion->factor;

            if (bccomp($factor, '0', self::FACTOR_SCALE) === 0) {
                continue;
            }

            $from = (int) $conversion->from_unit_id;
            $to = (int) $conversion->to_unit_id;

            $graph[$from][] = ['to' => $to, 'factor' => $factor];
            $graph[$to][] = ['to' => $from, 'factor' => bcdiv('1', $factor, self::FACTOR_SCALE)];
        }

        return $graph;
    }

    /**
     * Chemin multiplicatif le plus court entre deux unités (parcours en
     * largeur, profondeur bornée).
     *
     * @param  array<int, list<array{to: int, factor: string}>>  $graph
     */
    private function search(array $graph, int $from, int $to): ?string
    {
        /** @var list<array{unit: int, factor: string, depth: int}> $queue */
        $queue = [['unit' => $from, 'factor' => '1', 'depth' => 0]];
        $visited = [$from => true];

        while ($queue !== []) {
            $node = array_shift($queue);

            if ($node['depth'] >= self::MAX_DEPTH) {
                continue;
            }

            foreach ($graph[$node['unit']] ?? [] as $edge) {
                $factor = bcmul($node['factor'], $edge['factor'], self::FACTOR_SCALE);

                if ($edge['to'] === $to) {
                    return $this->trim($factor);
                }

                if (isset($visited[$edge['to']])) {
                    continue;
                }

                $visited[$edge['to']] = true;
                $queue[] = ['unit' => $edge['to'], 'factor' => $factor, 'depth' => $node['depth'] + 1];
            }
        }

        return null;
    }

    private function normalize(int|float|string $quantity): string
    {
        if (is_int($quantity)) {
            return (string) $quantity;
        }

        if (is_float($quantity)) {
            return rtrim(rtrim(sprintf('%.12F', $quantity), '0'), '.') ?: '0';
        }

        return trim($quantity) === '' ? '0' : trim($quantity);
    }

    /**
     * Retire les zéros inutiles : « 50.00000000 » → « 50 », « 0.50000000 » → « 0.5 ».
     */
    private function trim(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' || $trimmed === '-0' ? '0' : $trimmed;
    }
}
