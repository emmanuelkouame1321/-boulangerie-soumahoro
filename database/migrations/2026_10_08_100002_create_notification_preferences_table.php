<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préférences de notification par site et par type (cahier §22 et §24).
 *
 * Les canaux sont configurables : interne (écran /notifications) et
 * email. WhatsApp/SMS reste ouvert à une intégration future.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->restrictOnDelete();
            $table->string('type', 32);

            $table->boolean('internal')->default(true);
            $table->boolean('email')->default(false);

            $table->timestamps();

            $table->unique(['bakery_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
