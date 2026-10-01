<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes de matières premières (cahier §11.1, §11.2 et §24).
 *
 * Une commande appartient à un site et à un fournisseur. Elle n'est
 * jamais supprimée : une annulation est un changement de statut.
 * Le total est un entier FCFA, maintenu par PurchaseOrderService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('status', 32);

            $table->date('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->timestamp('validated_at')->nullable();

            // Total en entiers FCFA.
            $table->unsignedBigInteger('total')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'status']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
