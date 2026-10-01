<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fournisseurs d'un produit (cahier §10) — table reportée de la Phase 1.4.
 *
 * Un produit peut avoir plusieurs fournisseurs : un principal et des
 * secondaires, avec un prix négocié et un délai de livraison propres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_suppliers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);

            // Prix négocié en entiers FCFA.
            $table->unsignedBigInteger('negotiated_price')->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('supplier_reference')->nullable();

            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
