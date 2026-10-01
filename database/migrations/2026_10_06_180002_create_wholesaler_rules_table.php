<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paliers de ristourne administrables (cahier §13).
 *
 * Chaque site définit ses propres paliers : base de calcul, éventuelle
 * famille de produits, seuil bas, seuil haut et taux en pourcentage. Aucun
 * seuil n'est codé en dur ; l'écran Ristournes les administre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesaler_rules', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();

            $table->string('name');
            $table->string('basis', 16)->default('quantity');

            $table->unsignedBigInteger('min_threshold')->default(0);
            $table->unsignedBigInteger('max_threshold')->nullable();

            // Taux en pourcentage (ex. 2,5 %), jamais en fraction.
            $table->decimal('rate', 6, 3)->default(0);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['bakery_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_rules');
    }
};
