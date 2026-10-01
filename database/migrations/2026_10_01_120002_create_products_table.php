<?php

declare(strict_types=1);

use App\Enums\ProductType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produits et matières premières (cahier §4 et §24).
 *
 * Montants en entiers FCFA (cahier §53 : jamais de float pour un montant).
 * Les seuils sont des quantités décimales, comparées dans l'unité
 * principale du produit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->string('sku', 64)->unique();
            $table->string('name');

            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->string('type', 32)->default(ProductType::RawMaterial->value);

            $table->foreignId('primary_unit_id')->constrained('units');
            $table->foreignId('secondary_unit_id')->nullable()->constrained('units');

            // Prix en entiers FCFA.
            $table->unsignedBigInteger('purchase_price')->default(0);
            $table->unsignedBigInteger('sale_price')->default(0);
            $table->unsignedBigInteger('wholesale_price')->default(0);

            // Seuil et mode d'alerte (cahier §9.2 et §9.3).
            $table->decimal('alert_threshold', 18, 4)->nullable();
            $table->string('alert_mode', 32)->default('stock_threshold');

            // Durée limite de vente en jours (cahier §8.1).
            $table->unsignedSmallInteger('shelf_life_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
