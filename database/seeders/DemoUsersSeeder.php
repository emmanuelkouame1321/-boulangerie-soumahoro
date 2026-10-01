<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de démonstration par niveau (local et testing uniquement).
 *
 * Permet de tester chaque rôle depuis l'écran de connexion sans passer
 * par l'administration des utilisateurs. Jamais exécuté en production.
 */
class DemoUsersSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string, role: Role}>
     */
    private const USERS = [
        [
            'name' => 'Awa Koné',
            'email' => 'production@soumahoro.test',
            'role' => Role::ProductionManager,
        ],
        [
            'name' => 'Kofi Traoré',
            'email' => 'vente@soumahoro.test',
            'role' => Role::SalesManager,
        ],
        [
            'name' => 'Fatou Diabaté',
            'email' => 'caisse@soumahoro.test',
            'role' => Role::Cashier,
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoUsersSeeder ignoré : environnement non local.');

            return;
        }

        $bakery = Bakery::query()->orderBy('id')->first();

        foreach (self::USERS as $definition) {
            $user = User::query()->firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => 'password',
                ]
            );

            $user->syncRoles([$definition['role']->value]);

            if ($bakery !== null) {
                $user->bakeries()->syncWithoutDetaching([
                    $bakery->getKey() => ['is_default' => true],
                ]);
            }
        }
    }
}
