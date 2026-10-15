<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantité transférée d'un lot (cahier §19.3).
 *
 * Un lot quitte un site par un transfert : sans ce compteur, l'invariant
 * `initial = disponible + vendu + perdu` se casserait dès le premier
 * transfert et la réconciliation des lots deviendrait fausse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table): void {
            $table->decimal('transferred_quantity', 18, 4)->default(0)->after('lost_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table): void {
            $table->dropColumn('transferred_quantity');
        });
    }
};
