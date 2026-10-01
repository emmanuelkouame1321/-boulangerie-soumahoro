<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réservations de stock (cahier §9.1 et §24).
 *
 * Une réservation immobilise une quantité sans la consommer : le stock
 * disponible vaut « physique − réservé » (cahier §37.3). Elle est créée
 * par un document métier (ordre de production, commande…) via `source_*`,
 * puis libérée ou consommée par un mouvement de stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity', 18, 4);

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('status', 32)->default('active');
            $table->string('reference')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'product_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
