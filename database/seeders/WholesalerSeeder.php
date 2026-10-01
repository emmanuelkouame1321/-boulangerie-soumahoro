<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RebateBasis;
use App\Models\Bakery;
use App\Models\Customer;
use App\Models\Wholesaler;
use App\Models\WholesalerRule;
use Illuminate\Database\Seeder;

/**
 * Grossistes et paliers de ristourne de démonstration (local et testing).
 *
 * Les paliers d'amorçage reprennent l'exemple du cahier §13 ; ils restent
 * entièrement administrables depuis l'écran Ristournes.
 */
class WholesalerSeeder extends Seeder
{
    /** @var list<array{name: string, contact: string, phone: string, zone: string, quantity: string}> */
    private const WHOLESALERS = [
        ['name' => 'Hôtel Le Baobab', 'contact' => 'Awa Koné', 'phone' => '+225 07 09 08 07 06', 'zone' => 'Centre', 'quantity' => '120.0000'],
        ['name' => 'Restaurant Chez Awa', 'contact' => 'Awa Traoré', 'phone' => '+225 05 04 03 02 01', 'zone' => 'Cocody', 'quantity' => '60.0000'],
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

        foreach (self::WHOLESALERS as $definition) {
            $customer = Customer::query()
                ->where('bakery_id', $bakery->getKey())
                ->where('name', $definition['name'])
                ->first();

            Wholesaler::query()->updateOrCreate(
                ['bakery_id' => $bakery->getKey(), 'name' => $definition['name']],
                [
                    'customer_id' => $customer?->getKey(),
                    'contact_name' => $definition['contact'],
                    'phone' => $definition['phone'],
                    'zone' => $definition['zone'],
                    'client_type' => 'Grossiste',
                    'price_mode' => 'wholesale',
                    'usual_quantity' => $definition['quantity'],
                    'payment_condition' => 'monthly',
                    'is_active' => true,
                    'joined_on' => now()->subMonths(8)->toDateString(),
                ]
            );
        }

        foreach ((array) config('boulangerie.rebate.seed_rules', []) as $index => $rule) {
            /** @var array{min: int, max: int|null, rate: string} $rule */
            WholesalerRule::query()->updateOrCreate(
                [
                    'bakery_id' => $bakery->getKey(),
                    'basis' => RebateBasis::Quantity->value,
                    'min_threshold' => $rule['min'],
                ],
                [
                    'name' => 'Palier '.$rule['min'],
                    'max_threshold' => $rule['max'],
                    'rate' => $rule['rate'],
                    'is_active' => true,
                    'position' => $index,
                ]
            );
        }
    }
}
