<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employés (cahier §16.1).
 *
 * Un employé est rattaché à un site (isolation §19.2). Le poste et le type
 * de contrat sont des listes fermées amorçables ; le salaire de base est en
 * FCFA. Aucun employé n'est supprimé physiquement (désactivation ou
 * suppression douce).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 32)->nullable();
            $table->string('address')->nullable();

            $table->string('position', 64)->default('other');
            $table->string('contract_type', 16)->default('cdi');
            $table->unsignedBigInteger('base_salary')->default(0);
            $table->date('hired_on')->nullable();
            $table->string('status', 16)->default('active');

            $table->text('admin_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['bakery_id', 'last_name']);
            $table->index(['bakery_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
