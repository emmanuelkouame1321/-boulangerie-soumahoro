<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Export\ExportContent;
use App\Models\Export;
use App\Support\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Génération d'un export en arrière-plan (cahier §48, §53-13).
 *
 * Le job reconstruit le contenu depuis les filtres enregistrés, écrit le
 * fichier dans `storage/app/exports/` puis marque l'export prêt. Une
 * échoue est tracée sur la ligne (`status = failed`) plutôt que perdue.
 * L'écran de téléchargement le signale, la purge `boulangerie:exports-prune`
 * nettoie les fichiers expirés (7 jours).
 */
class GenerateExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Export $export) {}

    public function handle(ExportContent $content): void
    {
        $export = $this->export;

        try {
            $table = $content->build($export->kind, (array) $export->filters, $export->getAttribute('bakery_id'));

            $filename = 'export-'.$export->getKey().'.'.$export->format;
            $path = 'exports/'.$filename;

            Storage::disk('local')->makeDirectory('exports');
            $absolute = Storage::disk('local')->path($path);

            if ($export->format === 'xls') {
                ReportExport::writeXls($absolute, $table['label'], $table['headers'], $table['rows']);
            } else {
                ReportExport::writeCsv($absolute, $table['headers'], $table['rows']);
            }

            $export->update([
                'status' => Export::STATUS_READY,
                'path' => $path,
                'error' => null,
                'expires_at' => now()->addDays(7),
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => Export::STATUS_FAILED,
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->export->update([
            'status' => Export::STATUS_FAILED,
            'error' => mb_substr($exception?->getMessage() ?? 'Échec inconnu', 0, 500),
        ]);
    }
}
