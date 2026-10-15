<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logo du site (cahier §19.1, ADR-032).
 *
 * Le logo identifie la boulangerie dans l'application et sert d'icône à
 * l'onglet du navigateur (favicon). Il vit sur le disque `public`, dans
 * le dossier `bakeries/` : seul le chemin est stocké en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bakeries', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('bakeries', function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }
};
