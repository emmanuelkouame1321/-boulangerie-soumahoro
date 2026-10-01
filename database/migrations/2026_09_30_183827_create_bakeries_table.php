<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boulangeries / sites (cahier §19.1).
 *
 * Chaque site possède ses propres stocks, utilisateurs, productions,
 * commandes, ventes, charges et employés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakeries', function (Blueprint $table): void {
            $table->id();

            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 32)->nullable();

            // Responsable du site. Nullable : un site peut être créé avant
            // que son responsable ne soit affecté.
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakeries');
    }
};
