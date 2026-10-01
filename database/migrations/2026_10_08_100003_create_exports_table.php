<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exports générés en arrière-plan (cahier §48, file d'attente §53-13).
 *
 * Un export appartient à son demandeur et à son site, porte ses filtres,
 * et devient un fichier téléchargeable jusqu'à son expiration (7 jours).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->nullable()->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('kind', 32);
            $table->string('format', 8);
            $table->string('label');

            $table->json('filters')->nullable();

            $table->string('status', 16)->default('pending');
            $table->string('path')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
