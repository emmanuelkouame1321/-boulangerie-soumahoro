<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Périodes de paie (cahier §17).
 *
 * Une période est unique par site et par mois. Elle est préparée
 * (brouillon), puis validée et versée : dès la validation, les montants
 * sont figés et l'historique n'est plus recalculé (cahier §53-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();

            $table->string('reference', 64)->unique();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('started_on');
            $table->date('ended_on');
            $table->string('status', 16)->default('draft');

            $table->unsignedBigInteger('total_base')->default(0);
            $table->unsignedBigInteger('total_bonuses')->default(0);
            $table->unsignedBigInteger('total_advances')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('total_net')->default(0);

            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['bakery_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
