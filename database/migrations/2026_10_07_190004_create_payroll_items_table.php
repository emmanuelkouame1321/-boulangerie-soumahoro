<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de paie (cahier §17).
 *
 * Une ligne par employé et par période : salaire de base, primes, avances
 * et retenues (FCFA) ; le net est calculé côté service. La contrainte
 * unique garantit l'historique « un employé, une période ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->unsignedBigInteger('base_salary')->default(0);
            $table->unsignedBigInteger('bonuses')->default(0);
            $table->unsignedBigInteger('advances')->default(0);
            $table->unsignedBigInteger('deductions')->default(0);
            $table->unsignedBigInteger('net_salary')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
