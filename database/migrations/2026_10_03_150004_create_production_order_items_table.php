<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consommations d'un ordre de production (cahier §7.3 et §24).
 *
 * Chaque ligne correspond à une ressource de la recette, avec sa
 * quantité planifiée et sa quantité réellement consommée. `unit_cost`
 * fige le coût au moment de la consommation (cahier §45).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->string('deduction_mode', 32);

            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('actual_quantity', 18, 4)->nullable();

            // Coût unitaire et coût de ligne, en entiers FCFA.
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->unsignedBigInteger('line_cost')->default(0);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_items');
    }
};
