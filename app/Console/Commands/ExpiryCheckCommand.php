<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CheckBatchExpiry;
use App\Models\Bakery;
use Illuminate\Console\Command;

/**
 * Vérification de péremption (cahier §40, scheduler).
 *
 * Pour chaque site, planifie `CheckBatchExpiry` : statuts et alertes
 * des lots, conversion des lots expirés en perte. Le job tourne en
 * queue (sync en tests et en local).
 */
final class ExpiryCheckCommand extends Command
{
    protected $signature = 'boulangerie:expiry-check';

    protected $description = 'Détecte les lots à écouler et expirés, puis convertit les invendus expirés en pertes (cahier §8.2 et §40)';

    public function handle(): int
    {
        $date = now()->toDateString();
        $count = 0;

        foreach (Bakery::query()->orderBy('id')->pluck('id') as $bakeryId) {
            CheckBatchExpiry::dispatch((int) $bakeryId, $date);
            $count++;
        }

        $this->info("Vérification de péremption planifiée pour {$count} site(s).");

        return self::SUCCESS;
    }
}
