<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégories de produits (cahier §4.2 et §24).
 *
 * Catalogue partagé entre tous les sites ; seul le stock est propre à un
 * site (cahier §3.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 32)->nullable()->unique();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['position', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
