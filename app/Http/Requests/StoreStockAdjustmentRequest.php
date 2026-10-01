<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Correction manuelle d'un stock (cahier §3.2 et §3.3).
 *
 * Un ajustement est un mouvement comme un autre : il exige un motif et
 * n'écrase jamais une quantité — il ajoute ou retire une quantité tracée.
 */
class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjust', Stock::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'bakery_id' => ['nullable', 'integer', Rule::exists('bakeries', 'id')],
            'type' => [
                'required',
                Rule::in([
                    StockMovementType::AdjustmentIn->value,
                    StockMovementType::AdjustmentOut->value,
                ]),
            ],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Un motif est obligatoire pour toute correction de stock.',
            'type.in' => 'Un ajustement est soit une entrée, soit une sortie.',
        ];
    }
}
