<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sorties de produits finis d'une production (cahier §7.3 et §24).
 *
 * Une ligne représente l'entrée de produits finis générée par l'ordre.
 * Elle porte la quantité réellement produite et le coût unitaire retenu,
 * et sert de rattachement au lot correspondant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_outputs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 18, 4);

            // Coût unitaire de revient FCFA.
            $table->unsignedBigInteger('unit_cost')->default(0);

            $table->date('produced_on');

            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
    }
};
