<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Database\Seeder;

/**
 * Unités de mesure et conversions (cahier §4.3).
 *
 * Source de vérité : config/boulangerie.php. Idempotent : rejouer le
 * seeder met à jour les libellés sans créer de doublon.
 */
class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array{label: string, symbol: string, kind: string, position: int}> $units */
        $units = config('boulangerie.units', []);

        foreach ($units as $code => $definition) {
            Unit::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => $definition['label'],
                    'symbol' => $definition['symbol'],
                    'kind' => $definition['kind'],
                    'position' => $definition['position'],
                    'is_active' => true,
                ]
            );
        }

        $this->seedConversions();
    }

    private function seedConversions(): void
    {
        /** @var list<array{from: string, to: string, factor: int|float|string}> $conversions */
        $conversions = config('boulangerie.unit_conversions', []);

        foreach ($conversions as $conversion) {
            $from = Unit::query()->where('code', $conversion['from'])->first();
            $to = Unit::query()->where('code', $conversion['to'])->first();

            if ($from === null || $to === null) {
                continue;
            }

            UnitConversion::query()->updateOrCreate(
                ['from_unit_id' => $from->getKey(), 'to_unit_id' => $to->getKey()],
                ['factor' => $conversion['factor']]
            );
        }
    }
}
