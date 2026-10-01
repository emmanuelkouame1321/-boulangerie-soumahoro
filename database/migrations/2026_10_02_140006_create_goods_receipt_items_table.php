<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes d'une réception fournisseur (cahier §11.3).
 *
 * Chaque ligne porte la quantité réellement reçue, dans l'unité de la
 * ligne de commande. Les écarts (commandé vs reçu) sont calculés à
 * l'affichage depuis `purchase_order_items`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('unit_price')->default(0);

            $table->timestamps();

            $table->index('goods_receipt_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
