<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres propres à un site (cahier §19.1 « paramètres »).
 *
 * Permet de surcharger, site par site, les valeurs par défaut de
 * config/boulangerie.php (seuils, durées de vente, règles de péremption…).
 * Les valeurs sont stockées encodées en JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakery_settings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained()->cascadeOnDelete();
            $table->string('key', 128);
            $table->text('value')->nullable();

            $table->timestamps();

            $table->unique(['bakery_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakery_settings');
    }
};
