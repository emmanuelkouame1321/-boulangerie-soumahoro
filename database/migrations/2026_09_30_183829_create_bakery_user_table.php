<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affectation des utilisateurs aux sites (cahier §19.2 et §20.4).
 *
 * Un utilisateur peut être autorisé sur plusieurs sites ; `is_default`
 * désigne le site ouvert par défaut à la connexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakery_user', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bakery_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->primary(['user_id', 'bakery_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakery_user');
    }
};
