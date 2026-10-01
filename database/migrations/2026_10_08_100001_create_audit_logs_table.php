<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'audit (cahier §32).
 *
 * Chaque entrée conserve l'utilisateur (avec son nom à l'instant T, même
 * si le compte est ensuite supprimé), l'action, l'objet concerné, les
 * anciennes et nouvelles valeurs, l'IP, la date et le site.
 *
 * Aucune clé étrangère sur `bakery_id` : la suppression d'un site ne doit
 * jamais effacer le journal (§53-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('bakery_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();

            $table->string('action', 64);
            $table->string('auditable_type', 128)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['bakery_id', 'created_at']);
            $table->index('action');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
