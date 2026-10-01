<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catégories de charges (cahier §18).
 *
 * Amorçables depuis la configuration puis administrables par le
 * Super-Admin. Une catégorie n'est jamais supprimée : elle est désactivée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();

            $table->string('code', 64);
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['bakery_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
