<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vérification en deux étapes optionnelle (cahier §31).
 *
 * Le secret TOTP et les codes de secours sont chiffrés par le cast
 * `encrypted` du modèle : la base ne contient jamais la clé en clair.
 * `two_factor_confirmed_at` reste vide tant que l'utilisateur n'a pas
 * validé un premier code — un enrôlement commencé puis abandonné ne
 * verrouille donc pas le compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
