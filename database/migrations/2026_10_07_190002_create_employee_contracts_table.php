<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contrats de travail (cahier §16.1).
 *
 * Chaque contrat conserve sa date de début, sa date de fin éventuelle et le
 * salaire de base applicable : l'historique contractuel d'un employé n'est
 * jamais écrasé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('type', 16)->default('cdi');
            $table->unsignedBigInteger('base_salary')->default(0);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
