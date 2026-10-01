<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Fournisseurs de démonstration (local et testing uniquement).
 *
 * Les produits fournis sont rattachés quand le catalogue existe déjà ;
 * l'opération est idempotente.
 */
class SupplierSeeder extends Seeder
{
    /**
     * @var list<array{name: string, contact: string, phone: string, categories: string, primary: string}>
     */
    private const SUPPLIERS = [
        ['name' => 'Fournisseur Ivoire', 'contact' => 'Awa Koné', 'phone' => '07 00 00 10 01', 'categories' => 'Farine, levure', 'primary' => 'MP-FARINE'],
        ['name' => 'Minoterie Abidjan', 'contact' => 'Yao Serge', 'phone' => '05 00 00 20 02', 'categories' => 'Farine', 'primary' => 'MP-SEL'],
        ['name' => 'Agro Supply', 'contact' => 'Mariam Traoré', 'phone' => '01 00 00 30 03', 'categories' => 'Emballages, sel', 'primary' => 'MP-AMELIORANT'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('SupplierSeeder ignoré : environnement non local.');

            return;
        }

        /** @var array<string, int> $products */
        $products = Product::query()->pluck('id', 'sku')->all();

        foreach (self::SUPPLIERS as $definition) {
            $supplier = Supplier::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'contact_name' => $definition['contact'],
                    'phone' => $definition['phone'],
                    'categories' => $definition['categories'],
                    'payment_terms' => 'Comptant',
                    'delivery_delay_days' => 3,
                    'is_active' => true,
                ]
            );

            $sku = $definition['primary'];

            if (! isset($products[$sku])) {
                continue;
            }

            $supplier->products()->syncWithoutDetaching([
                $products[$sku] => [
                    'is_primary' => true,
                    'negotiated_price' => null,
                    'lead_time_days' => 3,
                ],
            ]);
        }
    }
}
