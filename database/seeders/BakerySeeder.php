<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Boulangeries de démonstration (environnements local et testing).
 *
 * Permet de valider immédiatement le multi-site, le sélecteur de
 * boulangerie et l'isolation des données. Jamais exécuté en production.
 */
class BakerySeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string, address: string, phone: string}>
     */
    private const SITES = [
        [
            'code' => 'BLG-COC',
            'name' => 'Boulangerie Cocody',
            'address' => 'Cocody, Abidjan',
            'phone' => '+225 07 00 00 01',
        ],
        [
            'code' => 'BLG-YOP',
            'name' => 'Boulangerie Yopougon',
            'address' => 'Yopougon, Abidjan',
            'phone' => '+225 07 00 00 02',
        ],
        [
            'code' => 'BLG-PLT',
            'name' => 'Boulangerie Plateau',
            'address' => 'Plateau, Abidjan',
            'phone' => '+225 07 00 00 03',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('BakerySeeder ignoré : environnement non local.');

            return;
        }

        $bakeries = [];

        foreach (self::SITES as $site) {
            $bakeries[] = Bakery::query()->updateOrCreate(
                ['code' => $site['code']],
                [
                    'name' => $site['name'],
                    'address' => $site['address'],
                    'phone' => $site['phone'],
                ]
            );
        }

        // Le Super-Admin est affecté à tous les sites, avec le premier
        // comme site par défaut.
        $superAdmin = User::query()
            ->where('email', (string) config('boulangerie.super_admin_seed.email'))
            ->first();

        if ($superAdmin === null) {
            return;
        }

        $superAdmin->syncRoles([Role::SuperAdmin->value]);

        $assignments = [];

        foreach ($bakeries as $index => $bakery) {
            $assignments[$bakery->getKey()] = ['is_default' => $index === 0];
        }

        $superAdmin->bakeries()->sync($assignments);
    }
}
