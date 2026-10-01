<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversions d'unités (cahier §4.3) : « 1 sac de farine = 50 kg ».
 *
 * Les conversions sont configurables et non codées en dur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('from_unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->cascadeOnDelete();

            // Quantité d'unité cible pour 1 unité source.
            // decimal : jamais de float pour une quantité métier.
            $table->decimal('factor', 18, 8);

            $table->timestamps();

            $table->unique(['from_unit_id', 'to_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
