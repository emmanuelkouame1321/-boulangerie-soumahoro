<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Domain\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Journalisation automatique d'un modèle (cahier §32).
 *
 * Opt-in : seuls les objets critiques du cahier (produits, recettes,
 * prix, mouvements de stock, ventes, pertes, charges, ristournes,
 * versements de salaire, réceptions et ordres de production) portent ce
 * trait. Un `status` modifié nomme l'action (`production_order.validated`)
 * au lieu d'un `.updated` générique.
 *
 * L'écriture suit la transaction du modèle : un rollback efface aussi
 * l'entrée d'audit.
 */
trait AuditsChanges
{
    protected static function bootAuditsChanges(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->created($model, self::auditSnapshotOf($model));
        });

        static::updated(function (Model $model): void {
            $diff = self::auditDiffOf($model);

            if ($diff === ['old' => [], 'new' => []]) {
                return;
            }

            $logger = app(AuditLogger::class);

            $logger->updated(
                $model,
                $logger->statusAction($model, $diff['new']),
                $diff['old'],
                $diff['new'],
            );
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->deleted(
                $model,
                app(AuditLogger::class)->action($model, 'deleted'),
                self::auditSnapshotOf($model),
            );
        });
    }

    /**
     * Attributs sensibles exclus, relations non incluses.
     *
     * @return array<string, mixed>
     */
    protected static function auditSnapshotOf(Model $model): array
    {
        $snapshot = [];

        foreach ($model->attributesToArray() as $key => $value) {
            if (in_array($key, AuditLogger::SENSITIVE, true)) {
                continue;
            }

            $snapshot[$key] = $value;
        }

        return $snapshot;
    }

    /**
     * Diff réelle de la dernière mise à jour : colonnes modifiées
     * seulement, ancienne et nouvelle valeur, hors colonnes de suivi et
     * hors attributs sensibles.
     *
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    protected static function auditDiffOf(Model $model): array
    {
        $old = [];
        $new = [];

        foreach ($model->getChanges() as $key => $value) {
            if ($key === 'created_at' || $key === 'updated_at') {
                continue;
            }

            if (in_array($key, AuditLogger::SENSITIVE, true)) {
                continue;
            }

            $old[$key] = $model->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }
}
