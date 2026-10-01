<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bakery;
use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * Clients de démonstration (local et testing uniquement).
 */
class CustomerSeeder extends Seeder
{
    /** @var list<array{name: string, phone: string}> */
    private const CUSTOMERS = [
        ['name' => 'Comptoir', 'phone' => ''],
        ['name' => 'Boutique du marché', 'phone' => '+225 07 01 02 03 04'],
        ['name' => 'Hôtel Le Baobab', 'phone' => '+225 07 09 08 07 06'],
        ['name' => 'Restaurant Chez Awa', 'phone' => '+225 05 04 03 02 01'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $bakery = Bakery::query()->orderBy('id')->first();

        if ($bakery === null) {
            return;
        }

        foreach (self::CUSTOMERS as $definition) {
            Customer::query()->updateOrCreate(
                ['bakery_id' => $bakery->getKey(), 'name' => $definition['name']],
                [
                    'phone' => $definition['phone'] !== '' ? $definition['phone'] : null,
                    'is_active' => true,
                ]
            );
        }
    }
}
