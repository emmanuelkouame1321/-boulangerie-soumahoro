<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charges d'exploitation (cahier §18).
 *
 * Une charge porte une catégorie, un montant (FCFA), un site, une date, un
 * bénéficiaire, un moyen de paiement, un justificatif facultatif et son
 * auteur. Aucune charge n'est supprimée (historique, §53-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('label')->nullable();

            $table->unsignedBigInteger('amount');
            $table->date('spent_on');

            $table->string('payee')->nullable();
            $table->string('method', 32)->default('cash');
            $table->string('attachment_path')->nullable();
            $table->text('comment')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['bakery_id', 'spent_on']);
            $table->index(['bakery_id', 'expense_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
