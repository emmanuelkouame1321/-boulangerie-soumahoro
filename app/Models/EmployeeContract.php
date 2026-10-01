<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractType;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\EmployeeContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Contrat de travail (cahier §16.1).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $employee_id
 * @property string $reference
 * @property ContractType $type
 * @property int $base_salary
 * @property Carbon $started_on
 * @property Carbon|null $ended_on
 * @property bool $is_active
 * @property string|null $note
 */
class EmployeeContract extends Model
{
    /** @use HasFactory<EmployeeContractFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'employee_id',
        'reference',
        'type',
        'base_salary',
        'started_on',
        'ended_on',
        'is_active',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'base_salary' => 'integer',
            'started_on' => 'date',
            'ended_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
