<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clôtures journalières (cahier §15).
 *
 * À la clôture, le responsable de vente inventorie les produits finis :
 * produit, vendu, restant, perdu, retourné et écart. La clôture est
 * unique par site et par date ; elle conserve les totaux pour le
 * reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('status', 32)->default('validated');
            $table->date('closing_date');

            $table->decimal('produced_total', 18, 4)->default(0);
            $table->decimal('sold_total', 18, 4)->default(0);
            $table->decimal('remaining_total', 18, 4)->default(0);
            $table->decimal('lost_total', 18, 4)->default(0);
            $table->decimal('returned_total', 18, 4)->default(0);
            $table->decimal('gap_total', 18, 4)->default(0);

            $table->text('note')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->unique(['bakery_id', 'closing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
    }
};
