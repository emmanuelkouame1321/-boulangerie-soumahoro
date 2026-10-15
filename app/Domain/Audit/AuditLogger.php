<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Sites\BakeryContext;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Écriture du journal d'audit (cahier §32 et §53-10).
 *
 * Seul point d'écriture d'audit_logs : capture l'utilisateur (nom figé à
 * l'instant T), l'action, l'objet, les anciennes et nouvelles valeurs,
 * l'IP, la date et le site. L'insertion suit la transaction courante :
 * un rollback efface aussi l'entrée d'audit.
 */
class AuditLogger
{
    /**
     * Attributs jamais journalisés même s'ils sont modifiés.
     *
     * @var list<string>
     */
    public const SENSITIVE = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function __construct(private readonly BakeryContext $context) {}

    /**
     * Événement métier explicite (validation, versement, permission…).
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?int $bakeryId = null,
    ): AuditLog {
        $user = $this->user();

        return AuditLog::query()->create([
            'bakery_id' => $bakeryId ?? $this->bakeryIdFor($subject),
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user instanceof User ? $user->name : null,
            'action' => $action,
            'auditable_type' => $subject === null ? null : $subject::class,
            'auditable_id' => $subject?->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $this->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Création d'un objet audité (trait AuditsChanges).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function created(Model $model, array $attributes): AuditLog
    {
        return $this->record($this->action($model, 'created'), $model, [], $attributes, $this->explicitBakeryId($model));
    }

    /**
     * Modification d'un objet audité : diff réel, jamais le bruit des
     * colonnes non modifiées. L'action est fournie par l'appelant, qui
     * nomme un changement de statut (`production_order.validated`)
     * plutôt qu'un `.updated` générique.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function updated(Model $model, string $action, array $old, array $new): AuditLog
    {
        return $this->record($action, $model, $old, $new, $this->explicitBakeryId($model));
    }

    /**
     * Suppression (logique ou réelle) d'un objet audité.
     *
     * @param  array<string, mixed>  $old
     */
    public function deleted(Model $model, string $action, array $old): AuditLog
    {
        return $this->record($action, $model, $old, [], $this->explicitBakeryId($model));
    }

    /**
     * Action canonique : `product.created`, `product.updated`…
     */
    public function action(Model $model, string $verb): string
    {
        return $this->slug($model).'.'.$verb;
    }

    /**
     * Nom de l'action dérivé d'un changement de statut :
     * `production_order.validated` plutôt que `production_order.updated`.
     *
     * @param  array<string, mixed>  $changes
     */
    public function statusAction(Model $model, array $changes): string
    {
        $status = $changes['status'] ?? $changes['state'] ?? null;

        if (is_string($status) && $status !== '') {
            return $this->slug($model).'.'.$status;
        }

        return $this->action($model, 'updated');
    }

    private function slug(Model $model): string
    {
        return Str::of(class_basename($model))->snake()->toString();
    }

    /**
     * Site : explicite, puis celui de l'objet, puis le contexte courant.
     */
    private function bakeryIdFor(?Model $subject): ?int
    {
        if ($subject === null) {
            return $this->context->current()?->getKey();
        }

        return $this->explicitBakeryId($subject);
    }

    private function explicitBakeryId(Model $subject): ?int
    {
        if ($subject->getAttribute('bakery_id') !== null) {
            return (int) $subject->getAttribute('bakery_id');
        }

        return $this->context->current()?->getKey();
    }

    private function user(): ?Authenticatable
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        return $user;
    }

    private function ip(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }
}
