<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Purge des exports expirés (cahier §48, ADR-021).
 *
 * Un export reste téléchargeable 7 jours ; passé ce délai, le fichier
 * est supprimé du disque puis la ligne est retirée. Les exports en
 * échec ou bloqués en attente plus de 24 h partent aussi.
 */
final class ExportPruneCommand extends Command
{
    protected $signature = 'boulangerie:exports-prune';

    protected $description = 'Supprime les exports expirés, leurs fichiers et les échecs anciens (cahier §48)';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $pruned = 0;

        Export::query()
            ->where(function ($query): void {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            })
            ->orWhere(function ($query): void {
                $query->where('status', '!=', Export::STATUS_READY)
                    ->where('created_at', '<', now()->subDay());
            })
            ->get()
            ->each(function (Export $export) use ($disk, &$pruned): void {
                if ($export->path !== null && $disk->exists($export->path)) {
                    $disk->delete($export->path);
                }

                $export->delete();
                $pruned++;
            });

        $this->info("Exports purgés : {$pruned}.");

        return self::SUCCESS;
    }
}
