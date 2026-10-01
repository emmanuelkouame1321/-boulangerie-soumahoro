<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de recherche manquants (cahier §48).
 *
 * Le grand livre et les rapports filtrent par site et par date sans
 * produit : l'index `[bakery_id, occurred_at]` évite un tri complet.
 * Les autres listes (produits, clients, fournisseurs, employés,
 * grossistes, ventes) sont déjà indexées par leurs migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['bakery_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['bakery_id', 'occurred_at']);
        });
    }
};
