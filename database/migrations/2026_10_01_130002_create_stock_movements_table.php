<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grand livre de stock (cahier §3.2, §3.3 et §29).
 *
 * Chaque variation de stock est un mouvement : utilisateur, site, date,
 * produit, quantité, unité, type, document source, motif, référence.
 *
 * Aucun mouvement n'est jamais supprimé physiquement (cahier §3.3) — la
 * contrainte de clé étrangère est donc en `restrictOnDelete`. Une
 * correction est un mouvement compensatoire, jamais une réécriture.
 *
 * `idempotency_key` porte l'index unique qui rend les opérations critiques
 * rejouables sans double effet (cahier §29).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->string('type', 32);

            // Quantité toujours positive : le sens est porté par le type.
            $table->decimal('quantity', 18, 4);
            // Stock physique après le mouvement, pour audit et dérivation rapide.
            $table->decimal('quantity_after', 18, 4)->nullable();

            // Document source (réception, production, vente, perte…).
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('reference')->nullable();
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();

            $table->string('idempotency_key', 191)->nullable()->unique();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['bakery_id', 'product_id', 'occurred_at']);
            $table->index(['bakery_id', 'type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
