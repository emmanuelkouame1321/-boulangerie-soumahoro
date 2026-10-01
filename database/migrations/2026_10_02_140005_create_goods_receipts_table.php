<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réceptions fournisseur (cahier §11.3 et §24).
 *
 * Une réception enregistre les quantités réellement livrées pour une
 * commande. Sa validation crée les mouvements d'entrée de stock et n'est
 * possible qu'une seule fois : `validated_at` fige la réception.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('status', 32)->default('draft');

            $table->timestamp('received_at');
            $table->timestamp('validated_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
