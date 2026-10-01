<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unités de mesure (cahier §4.3).
 *
 * Amorçage depuis config/boulangerie.php : aucune liste codée en dur dans
 * les services ou les vues.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 32)->unique();
            $table->string('label');
            $table->string('symbol', 16);

            // Nature de l'unité : poids, volume, contenant, pièce.
            $table->string('kind', 16)->default('count');

            // Position dans les listes et les sélecteurs.
            $table->unsignedSmallInteger('position')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['kind', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
