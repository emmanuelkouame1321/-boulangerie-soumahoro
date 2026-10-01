<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registres de perte (cahier §8.2, §15 et §24).
 *
 * Une perte est une opération critique : elle diminue le stock par un
 * mouvement `LossOut`, n'est jamais supprimée, porte une référence
 * `LOSS-{Ym}-{NNNN}` générée côté serveur et une valeur en FCFA. Elle
 * est soit déclarée manuellement, soit produite automatiquement par la
 * conversion des lots expirés (origine `expiry`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('reason', 32);
            $table->string('origin', 16)->default('manual');

            $table->date('occurred_on');
            $table->text('note')->nullable();

            $table->decimal('total_quantity', 18, 4)->default(0);
            $table->unsignedBigInteger('total_value')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['bakery_id', 'occurred_on']);
            $table->index(['bakery_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_records');
    }
};
