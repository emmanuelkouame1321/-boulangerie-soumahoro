<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de clôture journalière (cahier §15).
 *
 * Pour chaque produit fini : quantité produite, vendue, restante
 * inventoriée, perdue, retournée et écart calculé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closing_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('day_closing_id')->constrained('day_closings')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->decimal('produced_quantity', 18, 4)->default(0);
            $table->decimal('sold_quantity', 18, 4)->default(0);
            $table->decimal('remaining_quantity', 18, 4)->default(0);
            $table->decimal('lost_quantity', 18, 4)->default(0);
            $table->decimal('returned_quantity', 18, 4)->default(0);
            $table->decimal('gap', 18, 4)->default(0);

            $table->timestamps();

            $table->index('day_closing_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closing_items');
    }
};
