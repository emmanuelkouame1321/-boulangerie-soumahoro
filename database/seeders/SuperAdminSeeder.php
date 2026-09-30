<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Compte Super-Admin de démarrage.
 *
 * Refuse de s'exécuter hors local/testing : en production, le premier
 * compte doit être créé explicitement et son mot de passe changé.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('SuperAdminSeeder ignoré : environnement non local.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => (string) config('boulangerie.super_admin_seed.email')],
            [
                'name' => 'Super-Admin Soumahoro',
                'password' => (string) config('boulangerie.super_admin_seed.password'),
            ]
        );

        $user->syncRoles([Role::SuperAdmin->value]);
    }
}
