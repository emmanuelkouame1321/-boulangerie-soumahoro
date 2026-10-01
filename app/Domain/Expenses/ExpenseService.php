<?php

declare(strict_types=1);

namespace App\Domain\Expenses;

use App\Models\Bakery;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Charges d'exploitation (cahier §18).
 *
 * Une charge porte une référence `EXP-{Ym}-{NNNN}` générée côté serveur et
 * n'est jamais supprimée. Les catégories sont amorçables puis
 * administrables ; elles se désactivent, ne se suppriment pas.
 */
final class ExpenseService
{
    /**
     * @param  array{expense_category_id?: int|null, label?: string|null, amount: int, spent_on: string, payee?: string|null, method?: string|null, attachment_path?: string|null, comment?: string|null}  $data
     */
    public function record(Bakery $bakery, array $data, ?User $user = null): Expense
    {
        return DB::transaction(function () use ($bakery, $data, $user): Expense {
            return Expense::create([
                'bakery_id' => $bakery->getKey(),
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'reference' => $this->nextReference(),
                'label' => $data['label'] ?? null,
                'amount' => max(0, (int) $data['amount']),
                'spent_on' => $data['spent_on'],
                'payee' => $data['payee'] ?? null,
                'method' => $data['method'] ?? 'cash',
                'attachment_path' => $data['attachment_path'] ?? null,
                'comment' => $data['comment'] ?? null,
                'created_by' => $user?->getKey(),
            ]);
        });
    }

    public function createCategory(Bakery $bakery, string $code, string $name, int $position = 0): ExpenseCategory
    {
        return ExpenseCategory::create([
            'bakery_id' => $bakery->getKey(),
            'code' => $code,
            'name' => $name,
            'position' => $position,
            'is_active' => true,
        ]);
    }

    public function deactivateCategory(ExpenseCategory $category): ExpenseCategory
    {
        $category->update(['is_active' => false]);

        return $category->refresh();
    }

    private function nextReference(): string
    {
        $prefix = 'EXP-'.now()->format('Ym').'-';
        $sequence = Expense::withoutBakeryScope()->where('reference', 'like', "{$prefix}%")->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Expense::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
