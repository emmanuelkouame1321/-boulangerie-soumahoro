<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des prix (cahier §24).
 *
 * Le prix courant reste sur `products` ; cette table conserve les
 * changements pour l'analyse et l'audit. Un prix n'est jamais écrasé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // purchase | sale | wholesale
            $table->string('price_type', 16);

            $table->unsignedBigInteger('amount')->default(0);
            $table->date('effective_from')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'price_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
