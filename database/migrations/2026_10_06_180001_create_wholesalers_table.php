<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grossistes (cahier §12.1).
 *
 * Un grossiste appartient à un site (isolation §19.2) et peut être relié à
 * un compte client (`customers`) : les ventes grossistes existantes portent
 * déjà un `customer_id`, ce lien permet de calculer la ristourne depuis
 * l'historique réel sans dupliquer les ventes. Aucun grossiste n'est
 * supprimé physiquement (désactivation ou suppression douce).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesalers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->unique()->constrained('customers')->nullOnDelete();

            // Identité (cahier §12.1).
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('address')->nullable();
            $table->string('zone')->nullable();
            $table->string('client_type')->nullable();

            // Conditions commerciales.
            $table->string('price_mode', 16)->default('wholesale');
            $table->decimal('usual_quantity', 18, 4)->default(0);
            $table->string('payment_condition', 16)->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('joined_on')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['bakery_id', 'name']);
            $table->index(['bakery_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesalers');
    }
};
