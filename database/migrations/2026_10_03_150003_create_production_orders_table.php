<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordres de production (cahier §7.1, §7.4 et §24).
 *
 * Un ordre est rattaché à un site et à une recette. Il conserve la
 * quantité théorique, la quantité réellement produite, l'écart et son
 * motif (cahier §7.4), ainsi que les coûts théorique et réel (§45).
 * Aucun ordre n'est supprimé : une annulation est un changement de statut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('status', 32)->default('draft');

            // Nombre de lots / sacs lancés.
            $table->decimal('batch_count', 18, 4);

            // Rendement : quantité théorique et quantité réelle produite.
            $table->decimal('theoretical_quantity', 18, 4)->default(0);
            $table->decimal('actual_quantity', 18, 4)->nullable();

            // Écart = réel − théorique (cahier §37.1) et son motif (§7.4).
            $table->decimal('gap', 18, 4)->nullable();
            $table->string('gap_reason')->nullable();

            // Coûts en entiers FCFA (cahier §45).
            $table->unsignedBigInteger('theoretical_cost')->default(0);
            $table->unsignedBigInteger('actual_cost')->nullable();

            $table->date('produced_on');
            $table->timestamp('validated_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'status']);
            $table->index('produced_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
