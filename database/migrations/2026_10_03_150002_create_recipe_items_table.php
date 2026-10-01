<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ressources d'une recette (cahier §5.2, §5.3 et §24).
 *
 * Chaque ligne est une ressource nécessaire par lot de production. Le
 * mode de déduction distingue les quatre natures du cahier §5.3 :
 *  - `quantity` : ressource stockée physiquement, déduite du stock ;
 *  - `monetary` : ressource consommée selon un coût, sans mouvement ;
 *  - `cumulated` : ressource cumulée (fagot, glace…), sans mouvement ;
 *  - `manual`   : quantité réelle saisie lors de la production.
 *
 * `unit_cost` porte le coût unitaire FCFA configuré. Laissé nul pour une
 * ressource physique, le coût est alors le prix d'achat du produit au
 * moment de la consommation (cahier §45).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            // Quantité nécessaire par lot.
            $table->decimal('quantity', 18, 4);

            // Mode de déduction (config `boulangerie.recipe_deduction_modes`).
            $table->string('deduction_mode', 32);

            // Coût unitaire FCFA ; null = prix d'achat du produit consommé.
            $table->unsignedBigInteger('unit_cost')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index('recipe_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
