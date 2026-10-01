<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une charge (cahier §18).
 */
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('expense.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'spent_on' => ['required', 'date'],
            'payee' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', Rule::in(array_keys((array) config('boulangerie.payment_methods')))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_category_id' => 'catégorie',
            'amount' => 'montant',
            'spent_on' => 'date',
            'payee' => 'bénéficiaire',
            'method' => 'mode de paiement',
        ];
    }
}
