<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transferts de stock inter-sites (cahier §19.2 et §19.3).
 *
 * Un transfert est le seul objet du domaine rattaché à **deux** sites :
 * il porte donc deux clés étrangères explicites plutôt que le trait
 * `BelongsToBakery` (voir ADR-023). L'expédition crée un mouvement
 * `transfer_out` chez l'expéditeur, la réception un `transfer_in` chez le
 * destinataire : aucun stock n'est « en l'air » sans trace.
 *
 * Aucun transfert n'est supprimé : une erreur se corrige par un transfert
 * inverse ou un ajustement motivé (cahier §53-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table): void {
            $table->id();

            $table->string('reference', 32)->unique();

            // Expéditeur, puis destinataire.
            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('destination_bakery_id')->constrained('bakeries')->restrictOnDelete();

            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            // Lot expédié, si le produit est tracé par lot (cahier §19.3).
            $table->foreignId('production_batch_id')->nullable()
                ->constrained('production_batches')->nullOnDelete();

            $table->decimal('quantity', 18, 4);

            $table->string('status', 16)->default('draft');
            // Date métier du transfert (indépendante de l'horodatage
            // technique de l'expédition et de la réception).
            $table->date('transferred_on');
            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            // Expéditeur et réceptionnaire (cahier §19.3).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'transferred_on']);
            $table->index(['destination_bakery_id', 'transferred_on']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
