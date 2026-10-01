<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lots de produits finis (cahier §8 et §24).
 *
 * Chaque production de produit fini crée un lot : quantité initiale,
 * disponible, vendue, perdue, date de production et date limite de vente
 * (cahier §8). Le lot est conservé comme historique même épuisé ou
 * expiré. La date limite est calculée à partir de la durée configurée par
 * produit, ou de celle de la recette.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->foreignId('production_output_id')->nullable()->constrained('production_outputs')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->string('reference', 64)->unique();

            $table->decimal('initial_quantity', 18, 4);
            $table->decimal('available_quantity', 18, 4);
            $table->decimal('sold_quantity', 18, 4)->default(0);
            $table->decimal('lost_quantity', 18, 4)->default(0);

            $table->date('produced_on');
            $table->date('expires_on')->nullable();

            // Statut dérivé (config `boulangerie.statuses.batch`).
            $table->string('status', 32)->default('fresh');

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'product_id', 'expires_on']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
