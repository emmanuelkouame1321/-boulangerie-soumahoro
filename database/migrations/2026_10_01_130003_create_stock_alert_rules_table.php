<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règles d'alerte de stock (cahier §9.2 et §9.3).
 *
 * Deux modes :
 *  - `stock_threshold`     : stock actuel <= seuil ;
 *  - `cumulated_threshold` : consommation cumulée >= seuil, sur une
 *                            période configurable.
 *
 * Une règle sans `bakery_id` s'applique à tous les sites.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alert_rules', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->nullable()->constrained('bakeries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('mode', 32)->default('stock_threshold');
            $table->decimal('threshold', 18, 4);

            // Uniquement pour le mode cumulé (cf. config boulangerie.cumulation_periods).
            $table->string('cumulation_period', 32)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['product_id', 'mode']);
            $table->index(['bakery_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alert_rules');
    }
};
