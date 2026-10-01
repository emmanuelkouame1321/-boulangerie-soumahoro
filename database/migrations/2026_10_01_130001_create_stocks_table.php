<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock matérialisé par produit et par site (cahier §3.1 et §9.1).
 *
 * Cette table n'est qu'un cache de performance : la vérité reste le grand
 * livre `stock_movements`. Elle porte le stock physique, le stock réservé
 * et permet de dériver le stock disponible (physique − réservé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);

            $table->timestamps();

            $table->unique(['bakery_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
