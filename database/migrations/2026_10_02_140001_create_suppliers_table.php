<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fournisseurs (cahier §10 et §24).
 *
 * Le catalogue fournisseurs est partagé entre les sites, comme le
 * catalogue produits : seul un document (commande, réception) porte un
 * `bakery_id`. Un fournisseur n'est jamais supprimé physiquement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // Numéro d'identification (RCCM, compte contribuable…).
            $table->string('identification')->nullable();

            // Catégories fournies (farine, emballages…), saisie libre.
            $table->string('categories')->nullable();

            $table->string('payment_terms')->nullable();
            $table->unsignedSmallInteger('delivery_delay_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
