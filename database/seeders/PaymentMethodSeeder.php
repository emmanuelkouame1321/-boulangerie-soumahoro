<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Modes de paiement amorcés depuis `config('boulangerie.payment_methods')`.
 * Idempotent : le code est la clé de mise à jour.
 */
class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $position = 0;

        /** @var array<string, string> $methods */
        $methods = config('boulangerie.payment_methods', []);

        foreach ($methods as $code => $label) {
            $position += 10;

            PaymentMethod::query()->updateOrCreate(
                ['code' => $code],
                [
                    'label' => $label,
                    'position' => $position,
                    'is_active' => true,
                ]
            );
        }
    }
}
