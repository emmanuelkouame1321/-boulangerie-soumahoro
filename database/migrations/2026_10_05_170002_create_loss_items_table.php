<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de perte (cahier §8.2 et §24).
 *
 * Chaque ligne porte le produit, la quantité perdue, la valorisation en
 * FCFA au moment de la perte et le lot concerné lorsqu'il est connu (la
 * conversion automatique des lots expirés garde ainsi la trace du lot
 * qui a été transformé en perte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('loss_record_id')->constrained('loss_records')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();

            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('unit_value')->default(0);
            $table->unsignedBigInteger('line_value')->default(0);
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index('loss_record_id');
            $table->index('production_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_items');
    }
};
