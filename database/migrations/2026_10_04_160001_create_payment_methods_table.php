<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modes de paiement configurables (cahier §14 et §24).
 *
 * Amorçés depuis `config('boulangerie.payment_methods')` et administrables
 * par la suite. Le code porte la clé métier ; le libellé est affiché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 32)->unique();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
