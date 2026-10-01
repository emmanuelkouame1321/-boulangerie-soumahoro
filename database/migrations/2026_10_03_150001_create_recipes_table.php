<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recettes / nomenclatures de production (cahier §5, §6 et §24).
 *
 * Une recette décrit ce qu'il faut pour produire une quantité déterminée
 * de produit fini. Elle est partagée entre les sites, comme le catalogue
 * produits : les paramètres propres à un site passent par la production
 * elle-même. Le rendement théorique est exprimé par lot (ex. « 1 sac
 * → 300 pains », cahier §5.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->string('name');
            $table->string('reference', 64)->nullable()->unique();

            // Rendement théorique par lot : produit fini attendu.
            $table->decimal('yield_quantity', 18, 4)->default(0);

            // Durée de production et durée limite de vente (cahier §6).
            $table->unsignedInteger('production_time_minutes')->nullable();
            $table->unsignedInteger('shelf_life_days')->nullable();

            // Pertes autorisées, en pourcentage (cahier §6).
            $table->decimal('allowed_loss_percent', 8, 4)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
