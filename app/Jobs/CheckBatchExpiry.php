<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Inventory\ExpiryService;
use App\Models\Bakery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Vérification de péremption d'un site (cahier §40).
 *
 * Statuts et alertes des lots (à écouler / expirés), puis conversion des
 * invendus expirés en perte (§8.2). Idempotent : deux exécutions ne
 * produisent qu'une seule perte (§29).
 */
final class CheckBatchExpiry implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $bakeryId,
        public readonly string $date,
    ) {}

    public function handle(ExpiryService $expiry): void
    {
        $bakery = Bakery::query()->find($this->bakeryId);

        if ($bakery === null) {
            return;
        }

        $date = Carbon::parse($this->date);

        $expiry->refresh($bakery, $date);
        $expiry->convertExpired($bakery, $date);
    }
}
