<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertes de stock déclenchées (cahier §9.2, §9.3 et §22).
 *
 * Une alerte est acquittable et se referme d'elle-même lorsque la
 * condition disparaît. Elle conserve la valeur observée et le seuil pour
 * rester compréhensible après coup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('bakery_id')->constrained('bakeries')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('stock_alert_rule_id')->nullable()->constrained('stock_alert_rules')->nullOnDelete();

            $table->string('type', 32);
            $table->string('status', 32)->default('open');

            $table->decimal('threshold', 18, 4)->nullable();
            $table->decimal('observed_value', 18, 4)->nullable();
            $table->string('message')->nullable();

            $table->timestamp('triggered_at');

            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['bakery_id', 'product_id', 'status']);
            $table->index(['bakery_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
