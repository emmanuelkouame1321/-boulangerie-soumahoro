<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiements (cahier §14 et §24).
 *
 * Un paiement se rattache à une vente et à un mode de paiement. Les
 * montants sont des entiers FCFA. Une vente peut recevoir plusieurs
 * paiements (paiement partiel, complément).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('method', 32);
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('reference')->nullable();

            $table->timestamp('paid_at');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('sale_id');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
