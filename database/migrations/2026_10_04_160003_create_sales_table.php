<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventes (cahier §14, §24 et §25).
 *
 * Une vente appartient à un site. Elle n'est jamais supprimée : une
 * annulation est un changement de statut. Les montants sont des entiers
 * FCFA ; `idempotency_key` rend l'enregistrement rejouable sans double
 * déduction de stock (cahier §29).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('type', 32)->default('retail');
            $table->string('status', 32)->default('validated');

            $table->date('sale_date');

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid_total')->default(0);

            $table->string('idempotency_key', 191)->nullable()->unique();

            $table->text('note')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'sale_date']);
            $table->index(['bakery_id', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
