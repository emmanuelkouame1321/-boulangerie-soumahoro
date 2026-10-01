<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\EmployeeStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Employé (cahier §16.1).
 *
 * Un employé appartient à un site. Son poste et son type de contrat sont
 * des valeurs de configuration ; son salaire de base est en FCFA. Aucun
 * employé n'est supprimé physiquement (suppression douce).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $address
 * @property string $position
 * @property ContractType $contract_type
 * @property int $base_salary
 * @property Carbon|null $hired_on
 * @property EmployeeStatus $status
 * @property string|null $admin_note
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToBakery, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'address',
        'position',
        'contract_type',
        'base_salary',
        'hired_on',
        'status',
        'admin_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_type' => ContractType::class,
            'base_salary' => 'integer',
            'hired_on' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<EmployeeContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class)->orderByDesc('started_on');
    }

    /**
     * @return HasMany<PayrollItem, $this>
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * @return HasMany<PayrollPayment, $this>
     */
    public function payrollPayments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class)->orderByDesc('paid_at');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function positionLabel(): string
    {
        return (string) config("boulangerie.positions.{$this->position}", $this->position);
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', EmployeeStatus::Active->value);
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('position', 'like', "%{$term}%");
        });
    }
}
