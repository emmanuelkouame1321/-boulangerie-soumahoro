<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Charge d'exploitation (cahier §18).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $expense_category_id
 * @property string $reference
 * @property string|null $label
 * @property int $amount
 * @property Carbon $spent_on
 * @property string|null $payee
 * @property string $method
 * @property string|null $attachment_path
 * @property string|null $comment
 * @property int|null $created_by
 */
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'expense_category_id',
        'reference',
        'label',
        'amount',
        'spent_on',
        'payee',
        'method',
        'attachment_path',
        'comment',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'spent_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return (string) config("boulangerie.payment_methods.{$this->method}", $this->method);
    }

    /**
     * @param  Builder<Expense>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhere('label', 'like', "%{$term}%")
                ->orWhere('payee', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Expense>  $query
     */
    public function scopeBetween(Builder $query, string $from, string $to): void
    {
        $query->whereBetween('spent_on', [$from, $to]);
    }
}
