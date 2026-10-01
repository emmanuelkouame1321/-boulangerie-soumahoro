<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ristournes calculées (cahier §13).
 *
 * Une ristourne est unique par grossiste et par exercice : la ligne est
 * recalculée tant qu'elle reste « théorique », puis figée à la validation
 * et au versement (les données historiques ne sont pas écrasées, §53-11).
 * Les montants sont des entiers FCFA ; les quantités restent décimales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesaler_rebates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('wholesaler_id')->constrained('wholesalers')->cascadeOnDelete();
            $table->foreignId('wholesaler_rule_id')->nullable()->constrained('wholesaler_rules')->nullOnDelete();

            $table->unsignedSmallInteger('period_year');
            $table->string('basis', 16)->default('quantity');

            $table->decimal('base_quantity', 18, 4)->default(0);
            $table->unsignedBigInteger('base_value')->default(0);
            $table->decimal('rate', 6, 3)->default(0);

            $table->unsignedBigInteger('theoretical_value')->default(0);
            $table->unsignedBigInteger('validated_value')->default(0);
            $table->unsignedBigInteger('paid_value')->default(0);

            $table->string('status', 16)->default('projected');
            $table->date('settled_on')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['wholesaler_id', 'period_year']);
            $table->index(['bakery_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_rebates');
    }
};
