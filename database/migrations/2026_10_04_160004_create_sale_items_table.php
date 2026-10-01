<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de vente (cahier §14 et §24).
 *
 * Chaque ligne porte la quantité, le prix unitaire appliqué, la remise et
 * le total, en entiers FCFA. `unit_cost` fige le prix de revient disponible
 * pour l'analyse de marge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('line_total')->default(0);
            $table->unsignedBigInteger('unit_cost')->default(0);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
